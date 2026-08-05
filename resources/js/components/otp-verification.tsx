import { useForm } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { request, verify } from '@/routes/password/otp';

type Props = {
    status?: string;
};

export default function OtpVerification({ status }: Props) {
    // form untuk tombol kirim kode
    const requestForm = useForm<{ otp?: string }>({});
    // form untuk input & verifikasi kode
    const verifyForm = useForm<{ code: string }>({ code: '' });

    return (
        <div className="flex flex-col gap-4">
            <p className="text-sm text-muted-foreground">
                Demi keamanan akun, kami mengirim kode OTP 6 digit ke email
                kamu. Masukkan kode untuk bisa melanjutkan ganti password.
            </p>

            {status && (
                <p className="text-sm font-medium text-green-600">{status}</p>
            )}
            <InputError message={requestForm.errors.otp} />

            <Button
                type="button"
                variant="secondary"
                onClick={() => requestForm.post(request.url())}
                disabled={requestForm.processing}
            >
                {requestForm.processing && (
                    <LoaderCircle className="mr-2 size-4 animate-spin" />
                )}
                Kirim Kode OTP
            </Button>

            <div className="flex flex-col gap-2">
                <Label htmlFor="otp_code">Kode OTP</Label>
                <Input
                    id="otp_code"
                    inputMode="numeric"
                    autoComplete="one-time-code"
                    maxLength={6}
                    value={verifyForm.data.code}
                    onChange={(e) =>
                        verifyForm.setData(
                            'code',
                            // buang karakter selain angka saat mengetik
                            e.target.value.replace(/\D/g, ''),
                        )
                    }
                    placeholder="123456"
                />
                <InputError message={verifyForm.errors.code} />
            </div>

            <Button
                type="button"
                onClick={() => verifyForm.post(verify.url())}
                // aktif kalau kode sudah 6 digit
                disabled={
                    verifyForm.processing || verifyForm.data.code.length !== 6
                }
            >
                {verifyForm.processing && (
                    <LoaderCircle className="mr-2 size-4 animate-spin" />
                )}
                Verifikasi
            </Button>
        </div>
    );
}
