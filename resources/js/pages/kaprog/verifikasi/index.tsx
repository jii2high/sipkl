import { Head, Link } from '@inertiajs/react';
import SearchFilterBar from '@/components/search-filter-bar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { dashboard as kaprogDashboard } from '@/routes/kaprog';
import {
    index as verifikasiIndex,
    show as verifikasiShow,
} from '@/routes/kaprog/verifikasi';

export default function Verifikasi({ pengajuan }: any) {
    return (
        <>
            <Head title="Verifikasi Pengajuan" />

            <div className="space-y-6">
                <h2 className="text-2xl font-semibold">
                    Antrian Verifikasi PKL
                </h2>

                <SearchFilterBar
                    baseRoute="/kaprog/verifikasi"
                    searchPlaceholder="Cari nama siswa..."
                />

                {pengajuan?.data?.length === 0 && (
                    <Card>
                        <CardContent className="py-8 text-center text-muted-foreground">
                            Tidak ada pengajuan yang menunggu verifikasi.
                        </CardContent>
                    </Card>
                )}

                <div className="space-y-3">
                    {pengajuan?.data?.map((item: any) => (
                        <Card key={item.id}>
                            <CardHeader>
                                <CardTitle className="flex items-center justify-between text-base">
                                    {item.siswa?.user?.name}
                                    <Badge className="bg-yellow-100 text-yellow-800">
                                        Menunggu
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
                                </div>
                                <Link
                                    href={
                                        verifikasiShow({
                                            pengajuan: item.id,
                                        }).url
                                    }
                                >
                                    <Button variant="ghost">
                                        Lihat detail →
                                    </Button>
                                </Link>
                            </CardContent>
                        </Card>
                    ))}
                </div>

                {pengajuan?.last_page > 1 && (
                    <div className="flex justify-center gap-2">
                        {pengajuan.links
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

Verifikasi.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: kaprogDashboard() },
        { title: 'Verifikasi Pengajuan', href: verifikasiIndex() },
    ],
};
