import { Head, Link, usePage } from '@inertiajs/react';
import { Eye } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import kaprogRoutes from '@/routes/kaprog';

export default function VerifikasiIndex() {
    const { pengajuan } = usePage().props as any;

    return (
        <>
            <Head title="Verifikasi Pengajuan" />

            <div className="space-y-6 p-4">
                <h2 className="text-2xl font-semibold">
                    Antrian Verifikasi Pengajuan
                </h2>

                {pengajuan?.data?.length === 0 ? (
                    <Card>
                        <CardContent className="py-8 text-center text-muted-foreground">
                            Tidak ada pengajuan yang menunggu verifikasi.
                        </CardContent>
                    </Card>
                ) : (
                    <div className="space-y-3">
                        {pengajuan?.data?.map((item: any) => (
                            <Card key={item.id}>
                                <CardHeader>
                                    <CardTitle className="flex items-center justify-between text-base">
                                        <span>{item.siswa?.user?.name}</span>
                                        <span className="text-sm text-muted-foreground">
                                            {item.perusahaan?.nama}
                                        </span>
                                    </CardTitle>
                                </CardHeader>
                                <CardContent className="flex items-center justify-between">
                                    <span className="text-sm text-muted-foreground">
                                        {item.periode?.nama}
                                    </span>
                                    <Link
                                        href={
                                            kaprogRoutes.verifikasi.show({
                                                pengajuan: item.id,
                                            }).url
                                        }
                                    >
                                        <Button variant="ghost" size="sm">
                                            <Eye className="mr-1 h-4 w-4" />
                                            Lihat Detail
                                        </Button>
                                    </Link>
                                </CardContent>
                            </Card>
                        ))}
                    </div>
                )}
            </div>
        </>
    );
}

VerifikasiIndex.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/kaprog/dashboard' },
        { title: 'Verifikasi', href: kaprogRoutes.verifikasi.index() },
    ],
};
