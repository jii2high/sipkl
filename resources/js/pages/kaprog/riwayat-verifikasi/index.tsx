import { Head, Link } from '@inertiajs/react';
import SearchFilterBar from '@/components/search-filter-bar';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { dashboard as kaprogDashboard } from '@/routes/kaprog';
import { show as verifikasiShow } from '@/routes/kaprog/verifikasi';

export default function RiwayatVerifikasi({ pengajuan }: any) {
    const filterStatus = new URLSearchParams(window.location.search).get(
        'status',
    );

    return (
        <>
            <Head title="Riwayat Verifikasi" />

            <div className="space-y-6">
                <h2 className="text-2xl font-semibold">Riwayat Verifikasi</h2>

                <SearchFilterBar
                    baseRoute="/kaprog/riwayat-verifikasi"
                    searchPlaceholder="Cari nama siswa..."
                />

                <div className="flex gap-2">
                    {['disetujui', 'ditolak'].map((s) => (
                        <Link
                            key={s}
                            href={`/kaprog/riwayat-verifikasi?status=${s}`}
                            className={`rounded-full px-3 py-1 text-sm ${
                                filterStatus === s
                                    ? 'bg-primary text-primary-foreground'
                                    : 'bg-surface border'
                            }`}
                        >
                            {s === 'disetujui' ? 'Disetujui' : 'Ditolak'}
                        </Link>
                    ))}
                </div>

                {pengajuan?.data?.length === 0 && (
                    <Card>
                        <CardContent className="py-8 text-center text-muted-foreground">
                            Belum ada pengajuan yang diverifikasi.
                        </CardContent>
                    </Card>
                )}

                <div className="space-y-3">
                    {pengajuan?.data?.map((item: any) => (
                        <Card key={item.id}>
                            <CardHeader>
                                <CardTitle className="flex items-center justify-between text-base">
                                    {item.siswa?.user?.name}
                                    <Badge
                                        variant={
                                            item.status === 'disetujui'
                                                ? 'default'
                                                : 'destructive'
                                        }
                                    >
                                        {item.status === 'disetujui'
                                            ? 'Disetujui'
                                            : 'Ditolak'}
                                    </Badge>
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="flex items-center justify-between">
                                <div className="space-y-1 text-sm">
                                    <p>
                                        Perusahaan:{' '}
                                        <span className="font-medium">
                                            {item.perusahaan?.nama}
                                        </span>
                                    </p>
                                    <p>
                                        Periode:{' '}
                                        <span className="font-medium">
                                            {item.periode?.nama}
                                        </span>
                                    </p>
                                    {item.catatan_kaprog && (
                                        <p className="text-muted-foreground">
                                            Catatan: {item.catatan_kaprog}
                                        </p>
                                    )}
                                </div>
                                <Link
                                    href={
                                        verifikasiShow({
                                            pengajuan: item.id,
                                        }).url
                                    }
                                >
                                    <Badge variant="outline">Detail</Badge>
                                </Link>
                            </CardContent>
                        </Card>
                    ))}
                </div>

                {pengajuan?.last_page > 1 && (
                    <div className="flex justify-center gap-2">
                        {pengajuan.links
                            ?.filter((l: any) => !l.label.includes('...'))
                            .map((l: any) => (
                                <Link
                                    key={l.label}
                                    href={l.url}
                                    className={`rounded px-3 py-1 text-sm ${
                                        l.active
                                            ? 'bg-primary text-primary-foreground'
                                            : 'bg-surface border'
                                    }`}
                                >
                                    {l.label}
                                </Link>
                            ))}
                    </div>
                )}
            </div>
        </>
    );
}

RiwayatVerifikasi.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: kaprogDashboard() },
        { title: 'Riwayat Verifikasi' },
    ],
};
