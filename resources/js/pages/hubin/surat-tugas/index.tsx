import { Head, router } from '@inertiajs/react';
import { Download, Eye, FileCheck2, Power, Plus } from 'lucide-react';
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
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
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
    download as suratTugasDownload,
    index as suratTugasIndex,
    nonaktif as suratTugasNonaktif,
    store as suratTugasStore,
} from '@/routes/hubin/surat-tugas';

interface SuratTugasItem {
    id: number;
    status: string;
    lingkup: string;
    nomor_surat: string | null;
    tanggal_terbit: string | null;
    berlaku_hingga: string | null;
    file: string | null;
    periode: { id: number; nama: string };
    kompetensi_keahlian: { id: number; nama: string } | null;
}

interface Props {
    suratTugass: SuratTugasItem[];
    periodes: { id: number; nama: string }[];
    kompetensiKeahlians: { id: number; nama: string }[];
}

const PAGE_SIZE = 6;

function statusBadge(status: string) {
    if (status === 'aktif') {
        return <Badge className="bg-green-100 text-green-800">Aktif</Badge>;
    }

    if (status === 'draft') {
        return <Badge className="bg-yellow-100 text-yellow-800">Draft</Badge>;
    }

    return <Badge variant="secondary">Nonaktif</Badge>;
}

export default function SuratTugas({
    suratTugass,
    periodes,
    kompetensiKeahlians,
}: Props) {
    const [search, setSearch] = useState('');
    const [halaman, setHalaman] = useState(1);

    // Form tambah tugas
    const [formOpen, setFormOpen] = useState(false);
    const [form, setForm] = useState({
        periode_id: '',
        lingkup: 'global',
        kompetensi_keahlian_id: '',
        berlaku_hingga: '',
    });
    const [processing, setProcessing] = useState(false);

    // Dialog preview
    const [previewTarget, setPreviewTarget] = useState<SuratTugasItem | null>(
        null,
    );
    const [confirmAction, setConfirmAction] = useState<
        'terbit' | 'nonaktif' | 'batal' | null
    >(null);

    function resetForm() {
        setForm({
            periode_id: '',
            lingkup: 'global',
            kompetensi_keahlian_id: '',
            berlaku_hingga: '',
        });
    }

    function simpanDraft() {
        setProcessing(true);
        router.post(
            suratTugasStore().url,
            { ...form, langsung_terbit: false },
            {
                onSuccess: () => {
                    setFormOpen(false);
                    resetForm();
                    toast.success('Draft berhasil disimpan.');
                },
                onFinish: () => setProcessing(false),
            },
        );
    }

    function terbitkanLangsung() {
        setProcessing(true);
        router.post(
            suratTugasStore().url,
            { ...form, langsung_terbit: true },
            {
                onSuccess: () => {
                    setFormOpen(false);
                    resetForm();
                    toast.success('Surat Tugas berhasil diterbitkan.');
                },
                onFinish: () => setProcessing(false),
            },
        );
    }

    function terbitkanDraft(id: number) {
        router.post(
            `/hubin/surat-tugas/${id}/terbit`,
            {},
            {
                onSuccess: () => {
                    setPreviewTarget(null);
                    setConfirmAction(null);
                    toast.success('Surat Tugas berhasil diterbitkan.');
                },
            },
        );
    }

    function batalkanDraft(id: number) {
        router.delete(`/hubin/surat-tugas/${id}/tolak`, {
            onSuccess: () => {
                setPreviewTarget(null);
                setConfirmAction(null);
                toast.success('Draft dibatalkan.');
            },
        });
    }

    function nonaktifkan(id: number) {
        router.post(
            suratTugasNonaktif({ suratTugas: id }).url,
            {},
            {
                onSuccess: () => {
                    setPreviewTarget(null);
                    setConfirmAction(null);
                    toast.success('Surat Tugas dinonaktifkan.');
                },
            },
        );
    }

    const filtered = suratTugass.filter(
        (s) =>
            !search ||
            s.periode.nama.toLowerCase().includes(search.toLowerCase()) ||
            (s.kompetensi_keahlian?.nama ?? 'global')
                .toLowerCase()
                .includes(search.toLowerCase()),
    );

    const totalHalaman = Math.ceil(filtered.length / PAGE_SIZE);
    const itemsHalaman = filtered.slice(
        (halaman - 1) * PAGE_SIZE,
        halaman * PAGE_SIZE,
    );

    return (
        <>
            <Head title="Surat Tugas Monitoring" />
            <div className="flex flex-col gap-4 p-4">
                <div className="flex items-center justify-between">
                    <div>
                        <h2 className="text-2xl font-semibold">
                            Surat Tugas Monitoring
                        </h2>
                        <p className="text-sm text-muted-foreground">
                            Kelola surat tugas monitoring guru pembimbing.
                        </p>
                    </div>
                    <Button onClick={() => setFormOpen(true)}>
                        <Plus className="mr-1 size-4" />
                        Tambah Tugas
                    </Button>
                </div>

                <Input
                    placeholder="Cari periode atau jurusan..."
                    value={search}
                    onChange={(e) => {
                        setSearch(e.target.value);
                        setHalaman(1);
                    }}
                    className="max-w-sm"
                />

                <div className="rounded-lg border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead className="w-12">No</TableHead>
                                <TableHead>Lingkup</TableHead>
                                <TableHead>Periode</TableHead>
                                <TableHead>Berlaku S/d</TableHead>
                                <TableHead>Status</TableHead>
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
                                        Belum ada surat tugas.
                                    </TableCell>
                                </TableRow>
                            )}
                            {itemsHalaman.map((s, idx) => (
                                <TableRow key={s.id}>
                                    <TableCell>
                                        {(halaman - 1) * PAGE_SIZE + idx + 1}
                                    </TableCell>
                                    <TableCell>
                                        {s.lingkup === 'global' ? (
                                            <span className="text-muted-foreground">
                                                Global
                                            </span>
                                        ) : (
                                            s.kompetensi_keahlian?.nama
                                        )}
                                    </TableCell>
                                    <TableCell>{s.periode.nama}</TableCell>
                                    <TableCell>
                                        {s.berlaku_hingga ?? '—'}
                                    </TableCell>
                                    <TableCell>
                                        {statusBadge(s.status)}
                                    </TableCell>
                                    <TableCell className="text-right">
                                        <Button
                                            size="sm"
                                            variant="outline"
                                            onClick={() => {
                                                setPreviewTarget(s);
                                                setConfirmAction(null);
                                            }}
                                        >
                                            <Eye className="mr-1 size-4" />
                                            Lihat Surat
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

            {/* Dialog Tambah Tugas */}
            <Dialog
                open={formOpen}
                onOpenChange={(v) => {
                    setFormOpen(v);

                    if (!v) {
                        resetForm();
                    }
                }}
            >
                <DialogContent className="max-w-sm">
                    <DialogHeader>
                        <DialogTitle>Tambah Tugas Monitoring</DialogTitle>
                        <DialogDescription>
                            Buat sebagai draft atau langsung terbitkan.
                        </DialogDescription>
                    </DialogHeader>
                    <div className="flex flex-col gap-3">
                        <div className="space-y-1">
                            <Label>Periode PKL</Label>
                            <Select
                                value={form.periode_id}
                                onValueChange={(v) =>
                                    setForm({ ...form, periode_id: v })
                                }
                            >
                                <SelectTrigger>
                                    <SelectValue placeholder="Pilih periode" />
                                </SelectTrigger>
                                <SelectContent>
                                    {periodes.map((p) => (
                                        <SelectItem
                                            key={p.id}
                                            value={String(p.id)}
                                        >
                                            {p.nama}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                        <div className="space-y-1">
                            <Label>Lingkup</Label>
                            <Select
                                value={form.lingkup}
                                onValueChange={(v) =>
                                    setForm({
                                        ...form,
                                        lingkup: v,
                                        kompetensi_keahlian_id: '',
                                    })
                                }
                            >
                                <SelectTrigger>
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="global">
                                        Global (semua jurusan)
                                    </SelectItem>
                                    <SelectItem value="scoped">
                                        Per Kompetensi Keahlian
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        {form.lingkup === 'scoped' && (
                            <div className="space-y-1">
                                <Label>Kompetensi Keahlian</Label>
                                <Select
                                    value={form.kompetensi_keahlian_id}
                                    onValueChange={(v) =>
                                        setForm({
                                            ...form,
                                            kompetensi_keahlian_id: v,
                                        })
                                    }
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="Pilih jurusan" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {kompetensiKeahlians.map((kk) => (
                                            <SelectItem
                                                key={kk.id}
                                                value={String(kk.id)}
                                            >
                                                {kk.nama}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                        )}
                        <div className="space-y-1">
                            <Label>Berlaku Hingga</Label>
                            <Input
                                type="date"
                                value={form.berlaku_hingga}
                                onChange={(e) =>
                                    setForm({
                                        ...form,
                                        berlaku_hingga: e.target.value,
                                    })
                                }
                            />
                        </div>
                    </div>
                    <DialogFooter className="flex-col gap-2 sm:flex-row">
                        <Button
                            variant="outline"
                            onClick={() => {
                                setFormOpen(false);
                                resetForm();
                            }}
                        >
                            Batal
                        </Button>
                        <Button
                            variant="secondary"
                            disabled={processing}
                            onClick={simpanDraft}
                        >
                            Simpan Draft
                        </Button>
                        <Button
                            disabled={processing}
                            onClick={terbitkanLangsung}
                        >
                            <FileCheck2 className="mr-1 size-4" />
                            Terbitkan
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {/* Dialog Preview Surat */}
            <Dialog
                open={!!previewTarget}
                onOpenChange={(v) => {
                    if (!v) {
                        setPreviewTarget(null);
                        setConfirmAction(null);
                    }
                }}
            >
                <DialogContent className="max-w-lg">
                    <DialogHeader>
                        <DialogTitle>Detail Surat Tugas</DialogTitle>
                        <DialogDescription>
                            {previewTarget?.nomor_surat ??
                                'Draft — belum memiliki nomor surat'}
                        </DialogDescription>
                    </DialogHeader>
                    <div className="grid grid-cols-2 gap-3 text-sm">
                        <div>
                            <p className="text-muted-foreground">Status</p>
                            <div className="mt-1">
                                {previewTarget
                                    ? statusBadge(previewTarget.status)
                                    : null}
                            </div>
                        </div>
                        <div>
                            <p className="text-muted-foreground">Lingkup</p>
                            <p className="font-medium">
                                {previewTarget?.lingkup === 'global'
                                    ? 'Global (semua jurusan)'
                                    : previewTarget?.kompetensi_keahlian?.nama}
                            </p>
                        </div>
                        <div>
                            <p className="text-muted-foreground">Periode</p>
                            <p className="font-medium">
                                {previewTarget?.periode.nama}
                            </p>
                        </div>
                        <div>
                            <p className="text-muted-foreground">
                                Berlaku Hingga
                            </p>
                            <p className="font-medium">
                                {previewTarget?.berlaku_hingga ?? '—'}
                            </p>
                        </div>
                        {previewTarget?.tanggal_terbit && (
                            <div>
                                <p className="text-muted-foreground">
                                    Tanggal Terbit
                                </p>
                                <p className="font-medium">
                                    {previewTarget.tanggal_terbit}
                                </p>
                            </div>
                        )}
                    </div>

                    {confirmAction === 'terbit' && (
                        <div className="rounded-lg border border-yellow-200 bg-yellow-50 p-3 text-sm">
                            <p className="font-medium text-yellow-800">
                                Terbitkan surat ini?
                            </p>
                            <p className="text-yellow-700">
                                Nomor surat akan dibuat otomatis dan PDF akan
                                digenerate.
                            </p>
                            <div className="mt-2 flex gap-2">
                                <Button
                                    size="sm"
                                    variant="outline"
                                    onClick={() => setConfirmAction(null)}
                                >
                                    Batal
                                </Button>
                                <Button
                                    size="sm"
                                    onClick={() =>
                                        terbitkanDraft(previewTarget!.id)
                                    }
                                >
                                    Ya, Terbitkan
                                </Button>
                            </div>
                        </div>
                    )}
                    {confirmAction === 'batal' && (
                        <div className="rounded-lg border border-red-200 bg-red-50 p-3 text-sm">
                            <p className="font-medium text-red-800">
                                Batalkan draft ini?
                            </p>
                            <p className="text-red-700">
                                Draft akan dihapus permanen.
                            </p>
                            <div className="mt-2 flex gap-2">
                                <Button
                                    size="sm"
                                    variant="outline"
                                    onClick={() => setConfirmAction(null)}
                                >
                                    Batal
                                </Button>
                                <Button
                                    size="sm"
                                    variant="destructive"
                                    onClick={() =>
                                        batalkanDraft(previewTarget!.id)
                                    }
                                >
                                    Ya, Batalkan
                                </Button>
                            </div>
                        </div>
                    )}
                    {confirmAction === 'nonaktif' && (
                        <div className="rounded-lg border border-red-200 bg-red-50 p-3 text-sm">
                            <p className="font-medium text-red-800">
                                Nonaktifkan surat ini?
                            </p>
                            <p className="text-red-700">
                                Guru pembimbing tidak bisa melakukan kunjungan
                                setelah ini.
                            </p>
                            <div className="mt-2 flex gap-2">
                                <Button
                                    size="sm"
                                    variant="outline"
                                    onClick={() => setConfirmAction(null)}
                                >
                                    Batal
                                </Button>
                                <Button
                                    size="sm"
                                    variant="destructive"
                                    onClick={() =>
                                        nonaktifkan(previewTarget!.id)
                                    }
                                >
                                    Ya, Nonaktifkan
                                </Button>
                            </div>
                        </div>
                    )}

                    <DialogFooter className="flex-wrap gap-2">
                        <Button
                            variant="outline"
                            onClick={() => {
                                setPreviewTarget(null);
                                setConfirmAction(null);
                            }}
                        >
                            Tutup
                        </Button>
                        {previewTarget?.file && (
                            <Button variant="outline" asChild>
                                <a
                                    href={
                                        suratTugasDownload({
                                            suratTugas: previewTarget.id,
                                        }).url
                                    }
                                    target="_blank"
                                    rel="noopener noreferrer"
                                >
                                    <Download className="mr-1 size-4" />
                                    Unduh PDF
                                </a>
                            </Button>
                        )}
                        {previewTarget?.status === 'draft' &&
                            !confirmAction && (
                                <>
                                    <Button
                                        variant="destructive"
                                        onClick={() =>
                                            setConfirmAction('batal')
                                        }
                                    >
                                        Batalkan
                                    </Button>
                                    <Button
                                        onClick={() =>
                                            setConfirmAction('terbit')
                                        }
                                    >
                                        <FileCheck2 className="mr-1 size-4" />
                                        Terbitkan
                                    </Button>
                                </>
                            )}
                        {previewTarget?.status === 'aktif' &&
                            !confirmAction && (
                                <Button
                                    variant="destructive"
                                    onClick={() => setConfirmAction('nonaktif')}
                                >
                                    <Power className="mr-1 size-4" />
                                    Nonaktifkan
                                </Button>
                            )}
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}

SuratTugas.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: hubinDashboard() },
        { title: 'Surat Tugas Monitoring', href: suratTugasIndex() },
    ],
};
