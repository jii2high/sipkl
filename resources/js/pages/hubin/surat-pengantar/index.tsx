import { Head, Link } from '@inertiajs/react';
import { Building2, FileText } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { dashboard as hubinDashboard } from '@/routes/hubin';
import { preview as suratPengantarPreview } from '@/routes/hubin/surat-pengantar';

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
                            Periode aktif: <strong>{periodeAktif.nama}</strong>{' '}
                            — satu surat pengantar per perusahaan per jurusan.
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
                            <Card
                                key={`${p.perusahaan_id}-${p.kompetensi_keahlian_id}`}
                            >
                                <CardHeader>
                                    <CardTitle className="flex items-center justify-between text-base">
                                        <span className="flex items-center gap-2">
                                            <Building2 className="size-4 text-muted-foreground" />
                                            {p.perusahaan_nama}
                                            <Badge variant="secondary">
                                                {p.kompetensi_keahlian_nama}
                                            </Badge>
                                        </span>
                                        <span className="text-sm text-muted-foreground">
                                            {p.jumlah_siap} siswa siap
                                        </span>
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <div className="flex items-center justify-between">
                                        <span className="flex items-center gap-2 text-sm text-muted-foreground">
                                            <FileText className="size-4" />
                                            Kuota saran: {
                                                p.perusahaan_kuota
                                            }{' '}
                                            siswa
                                        </span>
                                        <Link
                                            href={
                                                suratPengantarPreview({
                                                    perusahaan: p.perusahaan_id,
                                                    kompetensiKeahlian:
                                                        p.kompetensi_keahlian_id,
                                                    periode: periodeAktif.id,
                                                }).url
                                            }
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
        { title: 'Bundling & Cetak' },
    ],
};
