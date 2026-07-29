import { Head, useForm, usePage } from '@inertiajs/react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import siswaRoutes from '@/routes/siswa';

export default function PengajuanCreate() {
    const { perusahaan, periode } = usePage().props as any;
    const { data, setData, post, processing, errors } = useForm({
        perusahaan_id: '',
    });

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        post(siswaRoutes.pengajuan.store().url, {
            preserveScroll: true,
        });
    }

    return (
        <>
            <Head title="Ajukan PKL" />

            <div className="space-y-6 p-4">
                <h2 className="text-2xl font-semibold">Ajukan PKL</h2>

                {periode ? (
                    <Card>
                        <CardHeader>
                            <CardTitle>Form Pengajuan PKL</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <form onSubmit={handleSubmit} className="space-y-4">
                                <div className="space-y-2">
                                    <p className="text-sm text-muted-foreground">
                                        Periode aktif:{' '}
                                        <span className="font-medium">
                                            {periode.nama}
                                        </span>
                                    </p>
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="perusahaan">
                                        Perusahaan Tujuan
                                    </Label>
                                    <Select
                                        value={data.perusahaan_id}
                                        onValueChange={(v) =>
                                            setData('perusahaan_id', v)
                                        }
                                    >
                                        <SelectTrigger>
                                            <SelectValue placeholder="Pilih perusahaan" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {perusahaan?.map((p: any) => (
                                                <SelectItem
                                                    key={p.id}
                                                    value={String(p.id)}
                                                >
                                                    {p.nama}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError
                                        message={errors.perusahaan_id}
                                    />
                                </div>

                                <Button type="submit" disabled={processing}>
                                    Kirim Pengajuan
                                </Button>
                            </form>
                        </CardContent>
                    </Card>
                ) : (
                    <Card>
                        <CardContent className="py-8 text-center text-muted-foreground">
                            Belum ada periode PKL aktif. Hubungi Hubin untuk
                            informasi lebih lanjut.
                        </CardContent>
                    </Card>
                )}
            </div>
        </>
    );
}

PengajuanCreate.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/siswa/dashboard' },
        { title: 'Pengajuan PKL', href: siswaRoutes.pengajuan.index() },
        { title: 'Ajukan PKL', href: siswaRoutes.pengajuan.create() },
    ],
};
