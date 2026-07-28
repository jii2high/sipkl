import { Head, useForm } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { update } from '@/routes/password/force-change';

export default function FirstLogin() {
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
                            Ini adalah login pertama Anda. Silakan ganti
                            password sebelum melanjutkan.
                        </p>
                    </div>

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
                </div>
            </div>
        </>
    );
}

FirstLogin.layout = null;
