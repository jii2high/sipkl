import { Head, useForm } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';
import OtpVerification from '@/components/otp-verification';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { update } from '@/routes/password/force-change';

type Props = {
    password_otp_verified?: boolean;
    status?: string;
};

export default function FirstLogin({
    password_otp_verified = false,
    status,
}: Props) {
    const { data, setData, post, processing, errors } = useForm({
        password: '',
        password_confirmation: '',
    });

    function handleClick() {
        post(update.url());
    }

    return (
        <>
            <Head title="Ganti Password" />
            <div className="flex min-h-screen items-center justify-center p-4">
                <div className="flex w-full max-w-sm flex-col gap-6">
                    <div className="flex flex-col gap-2 text-center">
                        <h1 className="text-2xl font-semibold">
                            Ganti Password
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            {password_otp_verified
                                ? 'Ini adalah login pertama Anda. Silakan ganti password sebelum melanjutkan.'
                                : 'Verifikasi OTP untuk keamanan akun sebelum ganti password.'}
                        </p>
                    </div>

                    {password_otp_verified ? ( // form password hanya muncul setelah OTP verified
                        <div className="flex flex-col gap-4">
                            <div className="flex flex-col gap-2">
                                <Label htmlFor="password">Password Baru</Label>
                                <Input
                                    id="password"
                                    type="password"
                                    value={data.password}
                                    onChange={(e) =>
                                        setData('password', e.target.value)
                                    }
                                    required
                                    autoFocus
                                />
                                {errors.password && (
                                    <p className="text-sm text-destructive">
                                        {errors.password}
                                    </p>
                                )}
                            </div>

                            <div className="flex flex-col gap-2">
                                <Label htmlFor="password_confirmation">
                                    Konfirmasi Password
                                </Label>
                                <Input
                                    id="password_confirmation"
                                    type="password"
                                    value={data.password_confirmation}
                                    onChange={(e) =>
                                        setData(
                                            'password_confirmation',
                                            e.target.value,
                                        )
                                    }
                                    required
                                />
                            </div>

                            <Button
                                onClick={handleClick}
                                className="w-full"
                                disabled={processing}
                            >
                                {processing && (
                                    <LoaderCircle className="mr-2 size-4 animate-spin" />
                                )}
                                Simpan Password
                            </Button>
                        </div>
                    ) : (
                        <OtpVerification status={status} />
                    )}
                </div>
            </div>
        </>
    );
}

FirstLogin.layout = null;
