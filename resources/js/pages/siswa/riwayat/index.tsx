import { Head, usePage } from '@inertiajs/react';
import { Clock } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

const statusBadge: Record<string, string> = {
    draft: 'bg-muted text-muted-foreground',
    diajukan_kaprog: 'bg-yellow-100 text-yellow-800',
    ditolak_kaprog: 'bg-red-100 text-red-800',
    siap_terbit: 'bg-green-100 text-green-800',
};

export default function RiwayatIndex() {
    const { riwayat } = usePage().props as any;

    return (
        <>
            <Head title="Riwayat PKL" />

            <div className="space-y-6 p-4">
                <h2 className="text-2xl font-semibold">Riwayat PKL</h2>

                {riwayat?.data?.length === 0 ? (
                    <Card>
                        <CardContent className="flex flex-col items-center gap-4 py-12">
                            <Clock className="h-12 w-12 text-muted-foreground" />
                            <p className="text-muted-foreground">
                                Belum ada riwayat pengajuan PKL.
                            </p>
                        </CardContent>
                    </Card>
                ) : (
                    <div className="space-y-4">
                        {riwayat?.data?.map((item: any) => (
                            <Card key={item.id}>
                                <CardHeader>
                                    <CardTitle className="flex items-center gap-2 text-base">
                                        {item.perusahaan?.nama}
                                        <Badge
                                            className={
                                                statusBadge[item.status] ?? ''
                                            }
                                        >
                                            {item.status}
                                        </Badge>
                                    </CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-2 text-sm">
                                    <p>Periode: {item.periode?.nama}</p>
                                    {item.catatan_kaprog && (
                                        <p>Catatan: {item.catatan_kaprog}</p>
                                    )}
                                    <p className="text-xs text-muted-foreground">
                                        Diajukan:{' '}
                                        {new Date(
                                            item.created_at,
                                        ).toLocaleDateString('id-ID')}
                                    </p>
                                </CardContent>
                            </Card>
                        ))}
                    </div>
                )}
            </div>
        </>
    );
}
