import { Head, router, usePage } from '@inertiajs/react';
import { AlertTriangle, Download, Eye, Send, XCircle } from 'lucide-react';
import { useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription,
    DialogFooter,
} from '@/components/ui/dialog';
import { Textarea } from '@/components/ui/textarea';
import {
    cetak as suratPengantarCetak,
    kirim as suratPengantarKirim,
    tolakBalasan as suratPengantarTolakBalasan,
} from '@/routes/hubin/surat-pengantar';
import { index as suratPengantarIndex } from '@/routes/hubin/surat-pengantar';

export default function Preview({
    perusahaan,
    kompetensiKeahlian,
    periode,
    pengajuans,
    balasanMasuk,
    lewatKuota,
}: any) {
    const [tab, setTab] = useState<'kirim' | 'balasan'>('kirim');
    const [tolakDialog, setTolakDialog] = useState<number | null>(null);
    const [catatan, setCatatan] = useState('');
    const [kirimLoading, setKirimLoading] = useState(false);
    const { flash } = usePage<{ flash?: { sukses?: string } }>().props;

    function handleCetak() {
        window.open(
            suratPengantarCetak({
                perusahaan: perusahaan.id,
                kompetensiKeahlian: kompetensiKeahlian.id,
                periode: periode.id,
            }).url,
            '_blank',
        );
    }

    function handleKirim() {
        if (
            !confirm(
                'Yakin ingin mengirim surat pengantar? Tindakan ini tidak dapat dibatalkan.',
            )
        ) {
            return;
        }

        setKirimLoading(true);
        router.post(
            suratPengantarKirim({
                perusahaan: perusahaan.id,
                kompetensiKeahlian: kompetensiKeahlian.id,
                periode: periode.id,
            }).url,
            {},
            {
                onFinish: () => setKirimLoading(false),
                preserveScroll: true,
            },
        );
    }

    function handleTolak(pengajuanId: number) {
        if (!catatan || catatan.length < 15) {
            return;
        }

        router.post(
            suratPengantarTolakBalasan({ pengajuan: pengajuanId }).url,
            { catatan },
            {
                preserveScroll: true,
                onSuccess: () => {
                    setTolakDialog(null);
                    setCatatan('');
                },
            },
        );
    }

    return (
        <>
            <Head title="Bundling Surat Pengantar" />

            <div className="mx-auto max-w-4xl space-y-6 p-4">
                <div className="flex items-center justify-between">
                    <div>
                        <h2 className="text-2xl font-semibold">
                            {perusahaan.nama}
                        </h2>
                        <p className="text-sm text-muted-foreground">
                            Jurusan: {kompetensiKeahlian.nama} · Periode:{' '}
                            {periode.nama}
                        </p>
                    </div>
                </div>

                {flash?.sukses && (
                    <div className="rounded-lg bg-green-50 p-3 text-sm text-green-700">
                        {flash.sukses}
                    </div>
                )}

                <div className="flex gap-1 rounded-lg border p-1">
                    <button
                        onClick={() => setTab('kirim')}
                        className={`flex-1 rounded-md px-4 py-2 text-sm font-medium transition ${
                            tab === 'kirim'
                                ? 'bg-primary text-primary-foreground'
                                : 'text-muted-foreground hover:bg-muted'
                        }`}
                    >
                        Kirim Surat Pengantar
                    </button>
                    <button
                        onClick={() => setTab('balasan')}
                        className={`flex-1 rounded-md px-4 py-2 text-sm font-medium transition ${
                            tab === 'balasan'
                                ? 'bg-primary text-primary-foreground'
                                : 'text-muted-foreground hover:bg-muted'
                        }`}
                    >
                        Balasan Masuk
                        {balasanMasuk?.length > 0 && (
                            <Badge variant="secondary" className="ml-2">
                                {balasanMasuk.length}
                            </Badge>
                        )}
                    </button>
                </div>

                {tab === 'kirim' && (
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center justify-between text-base">
                                <span>Daftar Siswa</span>
                                <div className="flex gap-2">
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        onClick={handleCetak}
                                    >
                                        <Download className="mr-1 size-4" />
                                        Cetak
                                    </Button>
                                    <Button
                                        size="sm"
                                        onClick={handleKirim}
                                        disabled={
                                            kirimLoading ||
                                            pengajuans?.length === 0
                                        }
                                    >
                                        <Send className="mr-1 size-4" />
                                        {kirimLoading ? 'Mengirim...' : 'Kirim'}
                                    </Button>
                                </div>
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            {lewatKuota && (
                                <div className="flex items-center gap-2 rounded-lg border border-yellow-200 bg-yellow-50 p-3 text-sm text-yellow-800">
                                    <AlertTriangle className="size-4 shrink-0" />
                                    <span>
                                        Jumlah siswa ({pengajuans.length})
                                        melebihi kuota saran perusahaan (
                                        {perusahaan.kuota_saran}). Surat tetap
                                        bisa diterbitkan.
                                    </span>
                                </div>
                            )}

                            {pengajuans?.length === 0 && (
                                <p className="py-4 text-center text-sm text-muted-foreground">
                                    Tidak ada pengajuan berstatus Disetujui
                                    untuk perusahaan ini.
                                </p>
                            )}

                            {pengajuans?.map((p: any, i: number) => (
                                <div
                                    key={p.id}
                                    className="flex items-center justify-between rounded-lg border p-3"
                                >
                                    <div>
                                        <p className="font-medium">
                                            {i + 1}.{' '}
                                            {p.siswa?.user?.name || '-'}
                                        </p>
                                        <p className="text-xs text-muted-foreground">
                                            {p.siswa?.nis} —{' '}
                                            {p.siswa?.kompetensi_keahlian
                                                ?.nama || '-'}
                                        </p>
                                    </div>
                                    <Badge variant="outline">Disetujui</Badge>
                                </div>
                            ))}
                        </CardContent>
                    </Card>
                )}

                {tab === 'balasan' && (
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">
                                Daftar Balasan Masuk
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            {balasanMasuk?.length === 0 && (
                                <p className="py-4 text-center text-sm text-muted-foreground">
                                    Belum ada surat balasan masuk untuk
                                    perusahaan ini.
                                </p>
                            )}

                            {balasanMasuk?.map((p: any) => (
                                <div
                                    key={p.id}
                                    className="flex items-center justify-between rounded-lg border p-3"
                                >
                                    <div>
                                        <p className="font-medium">
                                            {p.siswa?.user?.name || '-'}
                                        </p>
                                        <p className="text-xs text-muted-foreground">
                                            {p.siswa?.nis}
                                        </p>
                                    </div>
                                    <div className="flex items-center gap-2">
                                        {p.surat_balasan && (
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                asChild
                                            >
                                                <a
                                                    href={`/siswa/pengajuan/${p.id}/surat-balasan`}
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                >
                                                    <Eye className="mr-1 size-4" />
                                                    Lihat
                                                </a>
                                            </Button>
                                        )}
                                        <Button
                                            variant="destructive"
                                            size="sm"
                                            onClick={() => setTolakDialog(p.id)}
                                        >
                                            <XCircle className="mr-1 size-4" />
                                            Tolak
                                        </Button>
                                    </div>
                                </div>
                            ))}
                        </CardContent>
                    </Card>
                )}
            </div>

            <Dialog
                open={tolakDialog !== null}
                onOpenChange={(open) => {
                    if (!open) {
                        setTolakDialog(null);
                        setCatatan('');
                    }
                }}
            >
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Tolak Surat Balasan</DialogTitle>
                        <DialogDescription>
                            Surat balasan akan ditolak dan status pengajuan
                            kembali ke Disetujui. Siswa harus upload ulang.
                        </DialogDescription>
                    </DialogHeader>
                    <div className="space-y-2">
                        <label className="text-sm font-medium">
                            Alasan Penolakan (min. 15 karakter)
                        </label>
                        <Textarea
                            value={catatan}
                            onChange={(e) => setCatatan(e.target.value)}
                            placeholder="Jelaskan alasan penolakan..."
                            rows={3}
                        />
                        {catatan.length > 0 && catatan.length < 15 && (
                            <p className="text-xs text-red-500">
                                Minimal {15 - catatan.length} karakter lagi.
                            </p>
                        )}
                    </div>
                    <DialogFooter>
                        <Button
                            variant="outline"
                            onClick={() => {
                                setTolakDialog(null);
                                setCatatan('');
                            }}
                        >
                            Batal
                        </Button>
                        <Button
                            variant="destructive"
                            disabled={catatan.length < 15}
                            onClick={() =>
                                tolakDialog && handleTolak(tolakDialog)
                            }
                        >
                            Tolak & Kembalikan
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}

Preview.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/hubin/dashboard' },
        {
            title: 'Bundling & Cetak',
            href: suratPengantarIndex(),
        },
        { title: 'Preview' },
    ],
};
