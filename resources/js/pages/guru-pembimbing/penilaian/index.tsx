import { Head, Link } from '@inertiajs/react';
import { Building2, ClipboardList, FileText } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { dashboard as guruPembimbingDashboard } from '@/routes/guru-pembimbing';
import {
    index as penilaianIndex,
    create as penilaianCreate,
} from '@/routes/guru-pembimbing/penilaian';

export default function Penilaian({ penugasans }: any) {
    return (
        <>
            <Head title="Penilaian PKL" />

            <div className="space-y-6">
                <h2 className="text-2xl font-semibold">Penilaian PKL</h2>

                {penugasans?.length === 0 && (
                    <Card>
                        <CardContent className="py-8 text-center text-muted-foreground">
                            Belum ada siswa yang ditugaskan kepada Anda.
                        </CardContent>
                    </Card>
                )}

                <div className="space-y-3">
                    {penugasans?.map((p: any) => {
                        const formulir = p.formulir_penilaian;
                        const laporanAda = Boolean(
                            p.pengajuan?.laporan_akhir_uploaded_at,
                        );
                        const sudahSubmit = Boolean(formulir?.submitted_at);

                        return (
                            <Card key={p.id}>
                                <CardHeader>
                                    <CardTitle className="flex flex-wrap items-center justify-between gap-2 text-base">
                                        <span>
                                            {p.pengajuan?.siswa?.user?.name}
                                        </span>
                                        {sudahSubmit ? (
                                            <Badge className="bg-green-100 text-green-800">
                                                Sudah Dinilai
                                            </Badge>
                                        ) : laporanAda ? (
                                            <Badge className="bg-yellow-100 text-yellow-800">
                                                Menunggu Dinilai
                                            </Badge>
                                        ) : (
                                            <Badge
                                                variant="outline"
                                                className="text-muted-foreground"
                                            >
                                                Terkunci (Laporan Akhir belum
                                                ada)
                                            </Badge>
                                        )}
                                    </CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-3">
                                    <div className="flex items-center gap-1.5 text-sm text-muted-foreground">
                                        <Building2 className="size-4" />
                                        <span className="font-medium text-foreground">
                                            {p.pengajuan?.perusahaan?.nama}
                                        </span>
                                    </div>

                                    {!laporanAda && (
                                        <p className="flex items-start gap-1.5 rounded-lg bg-yellow-50 p-3 text-sm text-yellow-800">
                                            <FileText className="mt-0.5 size-4 shrink-0" />
                                            Formulir Penilaian terbuka setelah
                                            siswa mengunggah Laporan Akhir PKL
                                            (BR32).
                                        </p>
                                    )}

                                    {sudahSubmit && formulir && (
                                        <div className="space-y-2 rounded-lg border-t pt-3">
                                            <div className="flex items-center justify-between text-sm">
                                                <span className="text-muted-foreground">
                                                    Nilai Akhir
                                                </span>
                                                <span className="text-lg font-semibold">
                                                    {formulir.nilai_akhir ??
                                                        '-'}
                                                </span>
                                            </div>
                                            <p className="text-xs text-muted-foreground">
                                                Disubmit pada{' '}
                                                {new Date(
                                                    formulir.submitted_at,
                                                ).toLocaleDateString('id-ID')}
                                            </p>
                                        </div>
                                    )}

                                    {laporanAda && !sudahSubmit && (
                                        <Button asChild>
                                            <Link
                                                href={penilaianCreate({
                                                    penugasan: p.id,
                                                })}
                                            >
                                                <ClipboardList className="mr-1 size-4" />
                                                Isi Formulir Penilaian
                                            </Link>
                                        </Button>
                                    )}
                                </CardContent>
                            </Card>
                        );
                    })}
                </div>
            </div>
        </>
    );
}

Penilaian.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: guruPembimbingDashboard() },
        { title: 'Penilaian PKL', href: penilaianIndex() },
    ],
};
