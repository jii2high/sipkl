import { Head, router, useForm } from '@inertiajs/react';
import { Building2, MapPin, Upload } from 'lucide-react';
import { useRef, useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { dashboard as guruPembimbingDashboard } from '@/routes/guru-pembimbing';
import {
    index as kunjunganIndex,
    store as kunjunganStore,
} from '@/routes/guru-pembimbing/kunjungan';

export default function Kunjungan({ penugasans, suratTugasAktif }: any) {
    const [open, setOpen] = useState(false);
    const [penugasanId, setPenugasanId] = useState<number | null>(null);
    const fileRef = useRef<HTMLInputElement>(null);
    const { data, setData, errors, processing, reset } = useForm({
        tanggal_kunjungan: '',
        catatan: '',
        dokumentasi: [] as File[],
    });

    function openForm(penugasanId: number) {
        setPenugasanId(penugasanId);
        setOpen(true);
    }

    function closeForm() {
        setOpen(false);
        setPenugasanId(null);
        reset();
    }

    function submit(e: React.FormEvent) {
        e.preventDefault();

        if (!penugasanId) {
            return;
        }

        const formData = new FormData();
        formData.append('tanggal_kunjungan', data.tanggal_kunjungan);
        formData.append('catatan', data.catatan);

        data.dokumentasi.forEach((file) =>
            formData.append('dokumentasi[]', file),
        );

        router.post(kunjunganStore({ penugasan: penugasanId }).url, formData, {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: closeForm,
        });
    }

    return (
        <>
            <Head title="Kunjungan Monitoring" />

            <div className="space-y-6">
                <h2 className="text-2xl font-semibold">Kunjungan Monitoring</h2>

                {suratTugasAktif?.length === 0 && (
                    <div className="rounded-lg border border-yellow-200 bg-yellow-50 p-4 text-sm text-yellow-800">
                        Belum ada Surat Tugas Monitoring aktif. Laporan
                        kunjungan belum dapat diisi (menunggu Hubin menerbitkan
                        Surat Tugas).
                    </div>
                )}

                {penugasans?.length === 0 && (
                    <Card>
                        <CardContent className="py-8 text-center text-muted-foreground">
                            Belum ada siswa yang ditugaskan kepada Anda.
                        </CardContent>
                    </Card>
                )}

                <div className="space-y-3">
                    {penugasans?.map((p: any) => (
                        <Card key={p.id}>
                            <CardHeader>
                                <CardTitle className="flex flex-wrap items-center justify-between gap-2 text-base">
                                    <span>
                                        {p.pengajuan?.siswa?.user?.name}
                                    </span>
                                    <Badge className="bg-blue-100 text-blue-800">
                                        {p.pengajuan?.siswa?.kompetensi_keahlian
                                            ?.nama || '-'}
                                    </Badge>
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-3">
                                <div className="flex items-center gap-1.5 text-sm text-muted-foreground">
                                    <Building2 className="size-4" />
                                    <span className="font-medium text-foreground">
                                        {p.pengajuan?.perusahaan?.nama}
                                    </span>
                                </div>

                                <div className="flex items-center justify-between">
                                    <p className="text-sm text-muted-foreground">
                                        {p.kunjungan_monitorings?.length || 0}{' '}
                                        kunjungan tercatat
                                    </p>
                                    <Button
                                        size="sm"
                                        onClick={() => openForm(p.id)}
                                        disabled={suratTugasAktif?.length === 0}
                                    >
                                        <MapPin className="mr-1 size-4" />
                                        Isi Laporan Kunjungan
                                    </Button>
                                </div>

                                {p.kunjungan_monitorings?.length > 0 && (
                                    <div className="space-y-2 border-t pt-3">
                                        {p.kunjungan_monitorings.map(
                                            (k: any) => (
                                                <div
                                                    key={k.id}
                                                    className="bg-surface flex items-start justify-between gap-2 rounded-lg p-3 text-sm"
                                                >
                                                    <div>
                                                        <p className="font-medium">
                                                            {
                                                                k.tanggal_kunjungan
                                                            }
                                                        </p>
                                                        <p className="text-muted-foreground">
                                                            {k.catatan ||
                                                                'Tanpa catatan'}
                                                        </p>
                                                    </div>
                                                    <Badge variant="outline">
                                                        {k.dokumentasi_path
                                                            ? 'Ada dokumentasi'
                                                            : 'Tanpa dokumentasi'}
                                                    </Badge>
                                                </div>
                                            ),
                                        )}
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    ))}
                </div>
            </div>

            <Dialog open={open} onOpenChange={(v) => !v && closeForm()}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Laporan Kunjungan</DialogTitle>
                        <DialogDescription>
                            Isi laporan kunjungan monitoring ke perusahaan.
                        </DialogDescription>
                    </DialogHeader>

                    <form onSubmit={submit} className="flex flex-col gap-3">
                        <div className="space-y-2">
                            <Label htmlFor="tanggal_kunjungan">
                                Tanggal Kunjungan
                            </Label>
                            <Input
                                id="tanggal_kunjungan"
                                type="date"
                                value={data.tanggal_kunjungan}
                                onChange={(e) =>
                                    setData('tanggal_kunjungan', e.target.value)
                                }
                            />
                            {errors.tanggal_kunjungan && (
                                <p className="text-sm text-red-500">
                                    {errors.tanggal_kunjungan}
                                </p>
                            )}
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="catatan">Catatan</Label>
                            <Textarea
                                id="catatan"
                                value={data.catatan}
                                onChange={(e) =>
                                    setData('catatan', e.target.value)
                                }
                                placeholder="Ringkasan kegiatan kunjungan..."
                            />
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="dokumentasi">
                                Dokumentasi (opsional, multi-file)
                            </Label>
                            <Input
                                id="dokumentasi"
                                ref={fileRef}
                                type="file"
                                multiple
                                accept=".jpg,.jpeg,.png,.pdf,.mp4,.mov"
                                onChange={(e) => {
                                    const files = Array.from(
                                        e.target.files || [],
                                    );

                                    setData('dokumentasi', files);
                                }}
                            />
                            {data.dokumentasi.length > 0 && (
                                <p className="text-xs text-muted-foreground">
                                    {data.dokumentasi.length} file dipilih
                                </p>
                            )}
                        </div>

                        <Button type="submit" disabled={processing}>
                            <Upload className="mr-1 size-4" />
                            {processing ? 'Menyimpan...' : 'Simpan'}
                        </Button>
                    </form>
                </DialogContent>
            </Dialog>
        </>
    );
}

Kunjungan.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: guruPembimbingDashboard() },
        { title: 'Kunjungan Monitoring', href: kunjunganIndex() },
    ],
};
