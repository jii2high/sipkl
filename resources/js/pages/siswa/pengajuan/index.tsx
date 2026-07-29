import { Head, Link, usePage } from '@inertiajs/react';
import { FileText, Plus } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import siswaRoutes from '@/routes/siswa';

const statusBadge: Record<string, string> = {
    draft: 'bg-muted text-muted-foreground',
    diajukan_kaprog: 'bg-yellow-100 text-yellow-800',
    ditolak_kaprog: 'bg-red-100 text-red-800',
    siap_terbit: 'bg-green-100 text-green-800',
};

export default function PengajuanIndex() {
    const { pengajuan } = usePage().props as any;

    return (
        <>
            <Head title="Pengajuan PKL" />

            <div className="space-y-6 p-4">
                <div className="flex items-center justify-between">
                    <h2 className="text-2xl font-semibold">Pengajuan PKL</h2>
                    {!pengajuan && (
                        <Link href={siswaRoutes.pengajuan.create().url}>
                            <Button>
                                <Plus className="mr-2 h-4 w-4" />
                                Ajukan PKL
                            </Button>
                        </Link>
                    )}
                </div>

                {!pengajuan ? (
                    <Card>
                        <CardContent className="flex flex-col items-center gap-4 py-12">
                            <FileText className="h-12 w-12 text-muted-foreground" />
                            <p className="text-muted-foreground">
                                Belum ada pengajuan PKL. Klik "Ajukan PKL" untuk
                                memulai.
                            </p>
                        </CardContent>
                    </Card>
                ) : (
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                Status Pengajuan
                                <Badge
                                    className={
                                        statusBadge[pengajuan.status] ?? ''
                                    }
                                >
                                    {pengajuan.status_label ?? pengajuan.status}
                                </Badge>
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            <p>
                                <span className="font-medium">Perusahaan:</span>{' '}
                                {pengajuan.perusahaan?.nama}
                            </p>
                            <p>
                                <span className="font-medium">Periode:</span>{' '}
                                {pengajuan.periode?.nama}
                            </p>
                            {pengajuan.catatan_kaprog && (
                                <p>
                                    <span className="font-medium">
                                        Catatan Kaprog:
                                    </span>{' '}
                                    {pengajuan.catatan_kaprog}
                                </p>
                            )}

                            {pengajuan.histories?.length > 0 && (
                                <div className="mt-4 space-y-2">
                                    <h4 className="font-medium">
                                        Riwayat Status
                                    </h4>
                                    {pengajuan.histories.map((h: any) => (
                                        <div
                                            key={h.id}
                                            className="flex items-center gap-2 text-sm text-muted-foreground"
                                        >
                                            <span className="size-1.5 rounded-full bg-muted-foreground" />
                                            <span>
                                                {h.status_dari ?? '-'} →{' '}
                                                {h.status_ke}
                                            </span>
                                            <span className="text-xs">
                                                {h.actor?.name}
                                            </span>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </CardContent>
                    </Card>
                )}
            </div>
        </>
    );
}

PengajuanIndex.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/siswa/dashboard' },
        { title: 'Pengajuan PKL', href: siswaRoutes.pengajuan.index() },
    ],
};
