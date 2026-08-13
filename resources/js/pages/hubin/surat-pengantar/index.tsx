import { Head, router } from '@inertiajs/react';
import { Building2, Printer, Send } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { dashboard as hubinDashboard } from '@/routes/hubin';
import {
    cetak as suratPengantarCetak,
    kirim as suratPengantarKirim,
} from '@/routes/hubin/surat-pengantar';

interface SiswaItem {
    id: number;
    nama: string;
    nis: string;
}
interface PerusahaanItem {
    perusahaan_id: number;
    perusahaan_nama: string;
    perusahaan_alamat: string;
    perusahaan_kuota: number;
    kompetensi_keahlian_id: number;
    kompetensi_keahlian_nama: string;
    jumlah_siap: number;
    siswa: SiswaItem[];
}
interface Props {
    perusahaans: PerusahaanItem[];
    periodeAktif: { id: number; nama: string } | null;
    filters: { search: string | null };
}

const PAGE_SIZE = 6;

export default function Index({ perusahaans, periodeAktif, filters }: Props) {
    const [search, setSearch] = useState(filters.search ?? '');
    const [halaman, setHalaman] = useState(1);
    const [detailTarget, setDetailTarget] = useState<PerusahaanItem | null>(
        null,
    );
    const [confirmKirim, setConfirmKirim] = useState(false);
    const [mengirim, setMengirim] = useState(false);

    function handleSearch(v: string) {
        setSearch(v);
        setHalaman(1);
        router.get('/hubin/surat-pengantar', v ? { search: v } : {}, {
            preserveState: true,
            replace: true,
        });
    }

    function bukaDetail(p: PerusahaanItem) {
        setDetailTarget(p);
        setConfirmKirim(false);
    }

    function kirim() {
        if (!detailTarget || !periodeAktif) {
            return;
        }

        setMengirim(true);
        router.post(
            suratPengantarKirim({
                perusahaan: detailTarget.perusahaan_id,
                kompetensiKeahlian: detailTarget.kompetensi_keahlian_id,
                periode: periodeAktif.id,
            }).url,
            {},
            {
                onSuccess: () => {
                    setDetailTarget(null);
                    toast.success('Surat telah dikirim', {
                        description:
                            'Notifikasi sudah terkirim ke siswa terkait.',
                    });
                },
                onFinish: () => setMengirim(false),
            },
        );
    }

    const totalHalaman = Math.ceil(perusahaans.length / PAGE_SIZE);
    const itemsHalaman = perusahaans.slice(
        (halaman - 1) * PAGE_SIZE,
        halaman * PAGE_SIZE,
    );

    return (
        <>
            <Head title="Surat Pengantar PKL" />
            <div className="flex flex-col gap-4 p-4">
                <div>
                    <h2 className="text-2xl font-semibold">
                        Surat Pengantar PKL
                    </h2>
                    <p className="text-sm text-muted-foreground">
                        {periodeAktif ? (
                            <>
                                <span>Periode aktif: </span>
                                <strong>{periodeAktif.nama}</strong>
                            </>
                        ) : (
                            'Belum ada periode PKL aktif.'
                        )}
                    </p>
                </div>
                <Input
                    placeholder="Cari perusahaan atau jurusan..."
                    value={search}
                    onChange={(e) => handleSearch(e.target.value)}
                    className="max-w-sm"
                />
                <div className="rounded-lg border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead className="w-12">No</TableHead>
                                <TableHead>Perusahaan</TableHead>
                                <TableHead>Alamat</TableHead>
                                <TableHead>Jurusan</TableHead>
                                <TableHead className="text-center">
                                    Jumlah Siswa
                                </TableHead>
                                <TableHead className="text-right">
                                    Aksi
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {itemsHalaman.length === 0 && (
                                <TableRow>
                                    <TableCell
                                        colSpan={6}
                                        className="py-8 text-center text-muted-foreground"
                                    >
                                        {periodeAktif
                                            ? 'Tidak ada pengajuan yang siap diproses.'
                                            : 'Belum ada periode PKL aktif.'}
                                    </TableCell>
                                </TableRow>
                            )}
                            {itemsHalaman.map((p, idx) => (
                                <TableRow
                                    key={`${p.perusahaan_id}-${p.kompetensi_keahlian_id}`}
                                >
                                    <TableCell>
                                        {(halaman - 1) * PAGE_SIZE + idx + 1}
                                    </TableCell>
                                    <TableCell className="font-medium">
                                        <div className="flex items-center gap-2">
                                            <Building2 className="size-4 shrink-0 text-muted-foreground" />
                                            {p.perusahaan_nama}
                                        </div>
                                    </TableCell>
                                    <TableCell className="max-w-48 truncate text-sm text-muted-foreground">
                                        {p.perusahaan_alamat ?? '—'}
                                    </TableCell>
                                    <TableCell>
                                        <Badge variant="secondary">
                                            {p.kompetensi_keahlian_nama}
                                        </Badge>
                                    </TableCell>
                                    <TableCell className="text-center">
                                        <span className="font-medium">
                                            {p.jumlah_siap}
                                        </span>
                                        <span className="ml-1 font-normal text-muted-foreground">
                                            siswa
                                        </span>
                                    </TableCell>
                                    <TableCell className="text-right">
                                        <Button
                                            size="sm"
                                            variant="outline"
                                            onClick={() => bukaDetail(p)}
                                        >
                                            Lihat Detail
                                        </Button>
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </div>
                {totalHalaman > 1 && (
                    <div className="flex items-center justify-between text-sm text-muted-foreground">
                        <span>
                            Halaman {halaman} dari {totalHalaman}
                        </span>
                        <div className="flex gap-2">
                            <Button
                                size="sm"
                                variant="outline"
                                disabled={halaman <= 1}
                                onClick={() => setHalaman(halaman - 1)}
                            >
                                Sebelumnya
                            </Button>
                            <Button
                                size="sm"
                                variant="outline"
                                disabled={halaman >= totalHalaman}
                                onClick={() => setHalaman(halaman + 1)}
                            >
                                Berikutnya
                            </Button>
                        </div>
                    </div>
                )}
            </div>
            <Dialog
                open={!!detailTarget}
                onOpenChange={(v) => {
                    if (!v) {
                        setDetailTarget(null);
                    }
                }}
            >
                <DialogContent className="max-w-2xl">
                    <DialogHeader>
                        <div className="flex items-start justify-between gap-4 pr-8">
                            <div>
                                <DialogTitle>
                                    {detailTarget?.perusahaan_nama}
                                </DialogTitle>
                                <DialogDescription className="mt-1">
                                    {detailTarget?.perusahaan_alamat ?? '—'}
                                </DialogDescription>
                            </div>
                            {detailTarget && periodeAktif && (
                                <Button
                                    size="sm"
                                    variant="outline"
                                    className="shrink-0"
                                    asChild
                                >
                                    <a
                                        href={
                                            suratPengantarCetak({
                                                perusahaan:
                                                    detailTarget.perusahaan_id,
                                                kompetensiKeahlian:
                                                    detailTarget.kompetensi_keahlian_id,
                                                periode: periodeAktif.id,
                                            }).url
                                        }
                                        target="_blank"
                                        rel="noopener noreferrer"
                                    >
                                        <Printer className="mr-1 size-4" />
                                        Cetak
                                    </a>
                                </Button>
                            )}
                        </div>
                    </DialogHeader>
                    <div className="grid grid-cols-2 gap-3 text-sm">
                        <div>
                            <p className="text-muted-foreground">Jurusan</p>
                            <p className="font-medium">
                                {detailTarget?.kompetensi_keahlian_nama}
                            </p>
                        </div>
                        <div>
                            <p className="text-muted-foreground">
                                Kuota Saran Perusahaan
                            </p>
                            <p className="font-medium">
                                {detailTarget?.perusahaan_kuota ?? '—'} siswa
                            </p>
                        </div>
                    </div>
                    <div className="max-h-60 overflow-y-auto rounded-lg border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead className="w-10">No</TableHead>
                                    <TableHead>NIS</TableHead>
                                    <TableHead>Nama Siswa</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {detailTarget?.siswa.map((s, idx) => (
                                    <TableRow key={s.id}>
                                        <TableCell>{idx + 1}</TableCell>
                                        <TableCell className="font-mono text-sm">
                                            {s.nis}
                                        </TableCell>
                                        <TableCell>{s.nama}</TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </div>
                    <DialogFooter className="gap-2 sm:justify-between">
                        <Button
                            variant="outline"
                            onClick={() => setDetailTarget(null)}
                        >
                            Tutup
                        </Button>
                        {!confirmKirim ? (
                            <Button onClick={() => setConfirmKirim(true)}>
                                <Send className="mr-1 size-4" />
                                Kirim Surat
                            </Button>
                        ) : (
                            <div className="flex items-center gap-2">
                                <span className="text-sm text-muted-foreground">
                                    Yakin kirim surat?
                                </span>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    onClick={() => setConfirmKirim(false)}
                                >
                                    Batal
                                </Button>
                                <Button
                                    size="sm"
                                    disabled={mengirim}
                                    onClick={kirim}
                                >
                                    {mengirim ? 'Mengirim...' : 'Ya, Kirim'}
                                </Button>
                            </div>
                        )}
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}

Index.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: hubinDashboard() },
        { title: 'Surat Pengantar PKL' },
    ],
};
