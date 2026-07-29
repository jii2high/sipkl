import { Head, useForm, router } from '@inertiajs/react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { dashboard as siswaDashboard } from '@/routes/siswa';
import {
    index as pengajuanIndex,
    store as pengajuanStore,
    submit as pengajuanSubmit,
} from '@/routes/siswa/pengajuan';

const statusBadge: Record<string, string> = {
    draft: 'bg-muted text-muted-foreground',
    diajukan: 'bg-yellow-100 text-yellow-800',
    disetujui: 'bg-green-100 text-green-800',
    ditolak: 'bg-red-100 text-red-800',
};

export default function Pengajuan({
    pengajuan,
    perusahaan,
    periodeAktif,
}: any) {
    const { data, setData, post, errors } = useForm({ perusahaan_id: '' });

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        post(pengajuanStore().url);
    }

    function handleSubmitToKaprog() {
        if (confirm('Yakin ingin mengajukan PKL ke Kaprog?')) {
            router.post(pengajuanSubmit({ pengajuanPkl: pengajuan.id }).url);
        }
    }

    return (
        <>
            <Head title="Pengajuan PKL" />

            <div className="mx-auto max-w-2xl space-y-6">
                <h2 className="text-2xl font-semibold">Pengajuan PKL</h2>

                {!periodeAktif && (
                    <Card>
                        <CardContent className="py-8 text-center text-muted-foreground">
                            Belum ada periode PKL aktif.
                        </CardContent>
                    </Card>
                )}

                {periodeAktif && !pengajuan && (
                    <Card>
                        <CardHeader>
                            <CardTitle>Form Pengajuan PKL</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <form onSubmit={handleSubmit} className="space-y-4">
                                <div className="space-y-2">
                                    <label className="text-sm font-medium">
                                        Perusahaan Tujuan
                                    </label>
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
                                            {perusahaan.map((p: any) => (
                                                <SelectItem
                                                    key={p.id}
                                                    value={String(p.id)}
                                                >
                                                    {p.nama}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    {errors.perusahaan_id && (
                                        <p className="text-sm text-red-500">
                                            {errors.perusahaan_id}
                                        </p>
                                    )}
                                </div>

                                <p className="text-sm text-muted-foreground">
                                    Periode aktif:{' '}
                                    <strong>{periodeAktif.nama}</strong>
                                </p>

                                <Button type="submit">Simpan Draft</Button>
                            </form>
                        </CardContent>
                    </Card>
                )}

                {pengajuan && (
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center justify-between">
                                Pengajuan Aktif
                                <Badge
                                    className={
                                        statusBadge[pengajuan.status] || ''
                                    }
                                >
                                    {pengajuan.status_label || pengajuan.status}
                                </Badge>
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="grid grid-cols-2 gap-2 text-sm">
                                <span className="text-muted-foreground">
                                    Perusahaan
                                </span>
                                <span className="font-medium">
                                    {pengajuan.perusahaan?.nama}
                                </span>
                                <span className="text-muted-foreground">
                                    Periode
                                </span>
                                <span className="font-medium">
                                    {pengajuan.periode?.nama}
                                </span>
                            </div>

                            {(pengajuan.status === 'draft' ||
                                pengajuan.status === 'ditolak') && (
                                <Button
                                    onClick={handleSubmitToKaprog}
                                    className="w-full"
                                >
                                    Ajukan ke Kaprog
                                </Button>
                            )}

                            {pengajuan.catatan_kaprog && (
                                <div className="rounded-lg bg-yellow-50 p-3 text-sm">
                                    <p className="font-medium">
                                        Catatan Kaprog:
                                    </p>
                                    <p className="text-yellow-800">
                                        {pengajuan.catatan_kaprog}
                                    </p>
                                </div>
                            )}

                            {pengajuan.histories &&
                                pengajuan.histories.length > 0 && (
                                    <div className="space-y-2">
                                        <p className="text-sm font-medium">
                                            Riwayat Status:
                                        </p>
                                        <div className="space-y-2">
                                            {pengajuan.histories.map(
                                                (h: any) => (
                                                    <div
                                                        key={h.id}
                                                        className="flex items-start gap-2 text-sm"
                                                    >
                                                        <div className="mt-1 size-2 rounded-full bg-blue-500" />
                                                        <div>
                                                            <p>
                                                                <span className="font-medium">
                                                                    {h.status_dari ||
                                                                        '-'}
                                                                </span>
                                                                {' → '}
                                                                <span className="font-medium">
                                                                    {
                                                                        h.status_ke
                                                                    }
                                                                </span>
                                                            </p>
                                                            <p className="text-xs text-muted-foreground">
                                                                {h.actor?.name}{' '}
                                                                —{' '}
                                                                {new Date(
                                                                    h.created_at,
                                                                ).toLocaleDateString(
                                                                    'id-ID',
                                                                )}
                                                            </p>
                                                            {h.catatan && (
                                                                <p className="text-xs text-yellow-700">
                                                                    {h.catatan}
                                                                </p>
                                                            )}
                                                        </div>
                                                    </div>
                                                ),
                                            )}
                                        </div>
                                    </div>
                                )}
                        </CardContent>
                    </Card>
                )}
            </div>
        </>
    );
}

Pengajuan.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: siswaDashboard() },
        { title: 'Pengajuan PKL', href: pengajuanIndex() },
    ],
};
