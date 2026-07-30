import { Head, Link } from '@inertiajs/react';
import { FileText } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { dashboard as hubinDashboard } from '@/routes/hubin';
import {
    index as suratPengantarIndex,
    preview as suratPengantarPreview,
} from '@/routes/hubin/surat-pengantar';

export default function Index({ perusahaans, periodeAktif }: any) {
    return (
        <>
            <Head title="Bundling & Cetak Surat" />

            <div className="mx-auto max-w-4xl space-y-6 p-4">
                <div className="flex items-center justify-between">
                    <h2 className="text-2xl font-semibold">
                        Bundling & Cetak Surat
                    </h2>
                </div>

                {!periodeAktif && (
                    <Card>
                        <CardContent className="py-8 text-center text-muted-foreground">
                            Belum ada periode PKL aktif.
                        </CardContent>
                    </Card>
                )}

                {periodeAktif && (
                    <>
                        <p className="text-sm text-muted-foreground">
                            Periode aktif: <strong>{periodeAktif.nama}</strong>
                        </p>

                        {perusahaans?.length === 0 && (
                            <Card>
                                <CardContent className="py-8 text-center text-muted-foreground">
                                    Tidak ada perusahaan dengan pengajuan yang
                                    siap diproses.
                                </CardContent>
                            </Card>
                        )}

                        {perusahaans?.map((p: any) => (
                            <Card key={p.id}>
                                <CardHeader>
                                    <CardTitle className="flex items-center justify-between text-base">
                                        <span className="flex items-center gap-2">
                                            <FileText className="size-4 text-muted-foreground" />
                                            {p.nama}
                                        </span>
                                        <span className="text-sm text-muted-foreground">
                                            {p.jumlah_siap} siswa siap
                                        </span>
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <div className="flex items-center justify-between">
                                        <span className="text-sm text-muted-foreground">
                                            {p.alamat ||
                                                'Alamat tidak tersedia'}
                                        </span>
                                        <Link
                                            href={suratPengantarPreview({
                                                perusahaan: p.id,
                                                periode: periodeAktif.id,
                                            }).url}
                                        >
                                            <Button variant="ghost" size="sm">
                                                Lihat detail &rarr;
                                            </Button>
                                        </Link>
                                    </div>
                                </CardContent>
                            </Card>
                        ))}
                    </>
                )}
            </div>
        </>
    );
}

Index.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: hubinDashboard() },
        { title: 'Bundling & Cetak', href: suratPengantarIndex() },
    ],
};
