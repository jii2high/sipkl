import { Head, Link } from '@inertiajs/react';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { riwayat as riwayatRoute } from '@/routes/siswa';
import { dashboard as siswaDashboard } from '@/routes/siswa';

const statusBadge: Record<string, string> = {
    draft: 'bg-muted text-muted-foreground',
    diajukan: 'bg-yellow-100 text-yellow-800',
    disetujui: 'bg-green-100 text-green-800',
    ditolak: 'bg-red-100 text-red-800',
};

export default function Riwayat({ riwayat }: any) {
    return (
        <>
            <Head title="Riwayat PKL" />

            <div className="mx-auto max-w-3xl space-y-6">
                <h2 className="text-2xl font-semibold">Riwayat PKL</h2>

                {riwayat?.data?.length === 0 && (
                    <Card>
                        <CardContent className="py-8 text-center text-muted-foreground">
                            Belum ada riwayat PKL.
                        </CardContent>
                    </Card>
                )}

                {riwayat?.data?.map((item: any) => (
                    <Card key={item.id}>
                        <CardHeader>
                            <CardTitle className="flex items-center justify-between text-base">
                                {item.perusahaan?.nama}
                                <Badge
                                    className={statusBadge[item.status] || ''}
                                >
                                    {item.status}
                                </Badge>
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-1 text-sm">
                            <p>
                                Periode:{' '}
                                <span className="font-medium">
                                    {item.periode?.nama}
                                </span>
                            </p>
                            <p>
                                Diajukan:{' '}
                                <span className="font-medium">
                                    {new Date(
                                        item.created_at,
                                    ).toLocaleDateString('id-ID')}
                                </span>
                            </p>
                            {item.catatan_kaprog && (
                                <p className="text-yellow-700">
                                    Catatan: {item.catatan_kaprog}
                                </p>
                            )}
                        </CardContent>
                    </Card>
                ))}

                {riwayat?.last_page > 1 && (
                    <div className="flex justify-center gap-2">
                        {riwayat.links
                            ?.filter((l: any) => !l.label.includes('...'))
                            .map((link: any) => (
                                <Link
                                    key={link.label}
                                    href={link.url || '#'}
                                    className={`rounded px-3 py-1 text-sm ${link.active ? 'bg-primary text-white' : 'bg-muted'}`}
                                    preserveState
                                >
                                    {link.label}
                                </Link>
                            ))}
                    </div>
                )}
            </div>
        </>
    );
}

Riwayat.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: siswaDashboard() },
        { title: 'Riwayat PKL', href: riwayatRoute() },
    ],
};
