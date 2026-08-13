import { Head, router } from '@inertiajs/react';
import { Download, FileUp, Pencil, Plus, Trash2 } from 'lucide-react';
import { useRef, useState } from 'react';
import SearchFilterBar from '@/components/search-filter-bar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { dashboard as hubinDashboard } from '@/routes/hubin';

interface SiswaItem {
    id: number;
    nis: string;
    alamat: string | null;
    no_telp: string | null;
    user: { id: number; name: string; email: string };
    kompetensi_keahlian: { id: number; nama: string; acronym: string } | null;
    periode: { id: number; nama: string } | null;
}

interface JurusanItem {
    id: number;
    nama: string;
    acronym: string;
}

interface PeriodeItem {
    id: number;
    nama: string;
}

interface Props {
    siswa: { data: SiswaItem[]; links: any[]; last_page: number };
    jurusan: JurusanItem[];
    periodes: PeriodeItem[];
}

const formKosong = {
    nis: '',
    name: '',
    email: '',
    periode_id: '',
    kompetensi_keahlian_id: '',
    alamat: '',
    no_telp: '',
    password: '',
};

export default function KelolaSiswa({ siswa, jurusan, periodes }: Props) {
    const fileRef = useRef<HTMLInputElement>(null);

    // State dialog
    const [dialogBuka, setDialogBuka] = useState(false);
    const [modeTambah, setModeTambah] = useState(true);
    const [editTarget, setEditTarget] = useState<SiswaItem | null>(null);
    const [form, setForm] = useState(formKosong);
    const [menyimpan, setMenyimpan] = useState(false);
    const [duplikat, setDuplikat] = useState<{
        nama: string;
        nis: string;
    } | null>(null);
    const [errors, setErrors] = useState<Record<string, string>>({});

    function bukaTambah() {
        setModeTambah(true);
        setEditTarget(null);
        setForm(formKosong);
        setDuplikat(null);
        setErrors({});
        setDialogBuka(true);
    }

    function bukaEdit(s: SiswaItem) {
        setModeTambah(false);
        setEditTarget(s);
        setForm({
            nis: s.nis,
            name: s.user.name,
            email: s.user.email,
            periode_id: String(s.periode?.id ?? ''),
            kompetensi_keahlian_id: String(s.kompetensi_keahlian?.id ?? ''),
            alamat: s.alamat ?? '',
            no_telp: s.no_telp ?? '',
            password: '',
        });
        setDuplikat(null);
        setErrors({});
        setDialogBuka(true);
    }

    function simpan(force = false) {
        setMenyimpan(true);
        const payload = { ...form, ...(force ? { force: true } : {}) };

        const onSuccess = () => {
            setDialogBuka(false);
            setMenyimpan(false);
            setDuplikat(null);
        };

        const onError = (err: any) => {
            setErrors(err);
            setMenyimpan(false);
        };

        if (modeTambah) {
            router.post('/hubin/kelola-siswa', payload, {
                onSuccess,
                onError,
                onFinish: () => setMenyimpan(false),
            });
        } else {
            router.put(`/hubin/kelola-siswa/${editTarget!.user.id}`, payload, {
                onSuccess,
                onError,
                onFinish: () => setMenyimpan(false),
            });
        }
    }

    function hapus(s: SiswaItem) {
        if (confirm(`Hapus akun ${s.user.name}? (masuk recycle bin)`)) {
            router.delete(`/hubin/kelola-siswa/${s.user.id}`);
        }
    }

    function onImport(e: React.ChangeEvent<HTMLInputElement>) {
        const file = e.target.files?.[0];

        if (!file) {
            return;
        }

        const form = new FormData();
        form.append('file', file);
        router.post('/hubin/kelola-siswa/import', form, {
            onSuccess: () => {
                if (fileRef.current) {
                    fileRef.current.value = '';
                }
            },
        });
    }

    // Cek apakah form berubah dari data awal (untuk disable simpan saat edit)
    const adaPerubahan =
        modeTambah ||
        (editTarget !== null &&
            (form.name !== editTarget.user.name ||
                form.email !== editTarget.user.email ||
                form.nis !== editTarget.nis ||
                form.periode_id !== String(editTarget.periode?.id ?? '') ||
                form.kompetensi_keahlian_id !==
                    String(editTarget.kompetensi_keahlian?.id ?? '') ||
                form.alamat !== (editTarget.alamat ?? '') ||
                form.no_telp !== (editTarget.no_telp ?? '') ||
                form.password !== ''));

    return (
        <>
            <Head title="Kelola Siswa" />
            <div className="flex flex-col gap-4 p-4">
                <div className="flex items-center justify-between">
                    <h2 className="text-2xl font-semibold">Kelola Siswa</h2>
                    <div className="flex gap-2">
                        <Button
                            variant="outline"
                            onClick={() => fileRef.current?.click()}
                        >
                            <FileUp className="mr-1 size-4" /> Import
                        </Button>
                        <a href="/hubin/kelola-siswa/export">
                            <Button variant="outline">
                                <Download className="mr-1 size-4" /> Export
                            </Button>
                        </a>
                        <Button onClick={bukaTambah}>
                            <Plus className="mr-1 size-4" /> Tambah Siswa
                        </Button>
                    </div>
                </div>

                <SearchFilterBar
                    baseRoute="/hubin/kelola-siswa"
                    searchPlaceholder="Cari nama siswa..."
                    filters={[
                        {
                            key: 'kompetensi_keahlian_id',
                            label: 'Jurusan',
                            options: jurusan.map((j) => ({
                                label: j.nama,
                                value: String(j.id),
                            })),
                        },
                        {
                            key: 'periode_id',
                            label: 'Periode',
                            options: periodes.map((p) => ({
                                label: p.nama,
                                value: String(p.id),
                            })),
                        },
                    ]}
                />

                <div className="bg-surface overflow-hidden rounded-lg border">
                    <table className="w-full text-sm">
                        <thead className="text-left text-muted-foreground">
                            <tr className="border-b">
                                <th className="p-3">NIS</th>
                                <th className="p-3">Nama</th>
                                <th className="p-3">Jurusan</th>
                                <th className="p-3">Periode</th>
                                <th className="p-3">Email</th>
                                <th className="p-3">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            {siswa.data.length === 0 && (
                                <tr>
                                    <td
                                        colSpan={6}
                                        className="p-6 text-center text-muted-foreground"
                                    >
                                        Tidak ada data siswa.
                                    </td>
                                </tr>
                            )}
                            {siswa.data.map((s) => (
                                <tr
                                    key={s.id}
                                    className="border-b last:border-0"
                                >
                                    <td className="p-3 font-mono text-sm">
                                        {s.nis}
                                    </td>
                                    <td className="p-3 font-medium">
                                        {s.user.name}
                                    </td>
                                    <td className="p-3">
                                        <Badge variant="secondary">
                                            {s.kompetensi_keahlian?.acronym ??
                                                '-'}
                                        </Badge>
                                    </td>
                                    <td className="p-3 text-muted-foreground">
                                        {s.periode?.nama ?? '-'}
                                    </td>
                                    <td className="p-3 text-muted-foreground">
                                        {s.user.email}
                                    </td>
                                    <td className="p-3">
                                        <DropdownMenu>
                                            <DropdownMenuTrigger asChild>
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                >
                                                    •••
                                                </Button>
                                            </DropdownMenuTrigger>
                                            <DropdownMenuContent align="end">
                                                <DropdownMenuItem
                                                    onClick={() => bukaEdit(s)}
                                                >
                                                    <Pencil className="mr-2 size-4" />{' '}
                                                    Edit
                                                </DropdownMenuItem>
                                                <DropdownMenuItem
                                                    className="text-destructive"
                                                    onClick={() => hapus(s)}
                                                >
                                                    <Trash2 className="mr-2 size-4" />{' '}
                                                    Hapus
                                                </DropdownMenuItem>
                                            </DropdownMenuContent>
                                        </DropdownMenu>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>

                {siswa.last_page > 1 && (
                    <div className="flex justify-center gap-2">
                        {siswa.links
                            ?.filter((l: any) => !l.label.includes('...'))
                            .map((link: any) => (
                                <a
                                    key={link.label}
                                    href={link.url || '#'}
                                    className={`rounded px-3 py-1 text-sm ${link.active ? 'bg-primary text-primary-foreground' : 'bg-muted'}`}
                                >
                                    {link.label}
                                </a>
                            ))}
                    </div>
                )}
            </div>

            {/* Input import (hidden) */}
            <input
                ref={fileRef}
                type="file"
                accept=".xlsx,.xls"
                className="hidden"
                onChange={onImport}
            />

            {/* Dialog Tambah / Edit Siswa */}
            <Dialog open={dialogBuka} onOpenChange={setDialogBuka}>
                <DialogContent className="max-w-lg">
                    <DialogHeader>
                        <DialogTitle>
                            {modeTambah ? 'Tambah Siswa' : 'Edit Siswa'}
                        </DialogTitle>
                    </DialogHeader>

                    {/* Alert duplikat */}
                    {duplikat && (
                        <div className="rounded-md border border-destructive/40 bg-destructive/10 p-3 text-sm text-destructive">
                            Data telah dimiliki oleh{' '}
                            <strong>
                                {duplikat.nama}; {duplikat.nis}
                            </strong>
                            . Yakin ingin menimpa?
                            <div className="mt-2 flex gap-2">
                                <Button
                                    size="sm"
                                    variant="destructive"
                                    onClick={() => simpan(true)}
                                >
                                    Ya, Timpa
                                </Button>
                                <Button
                                    size="sm"
                                    variant="ghost"
                                    onClick={() => setDuplikat(null)}
                                >
                                    Batal
                                </Button>
                            </div>
                        </div>
                    )}

                    <div className="grid gap-3">
                        <div className="grid grid-cols-2 gap-3">
                            <div>
                                <Label>NIS</Label>
                                <Input
                                    value={form.nis}
                                    onChange={(e) =>
                                        setForm({
                                            ...form,
                                            nis: e.target.value,
                                        })
                                    }
                                    placeholder="Nomor Induk Siswa"
                                />
                                {errors.nis && (
                                    <p className="mt-1 text-xs text-destructive">
                                        {errors.nis}
                                    </p>
                                )}
                            </div>
                            <div>
                                <Label>Nama Lengkap</Label>
                                <Input
                                    value={form.name}
                                    onChange={(e) =>
                                        setForm({
                                            ...form,
                                            name: e.target.value,
                                        })
                                    }
                                    placeholder="Nama siswa"
                                />
                                {errors.name && (
                                    <p className="mt-1 text-xs text-destructive">
                                        {errors.name}
                                    </p>
                                )}
                            </div>
                        </div>

                        <div>
                            <Label>Periode</Label>
                            <Select
                                value={form.periode_id}
                                onValueChange={(v) =>
                                    setForm({ ...form, periode_id: v })
                                }
                            >
                                <SelectTrigger>
                                    <SelectValue placeholder="Pilih periode aktif" />
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
                            {errors.periode_id && (
                                <p className="mt-1 text-xs text-destructive">
                                    {errors.periode_id}
                                </p>
                            )}
                        </div>

                        <div>
                            <Label>Jurusan</Label>
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
                                    {jurusan.map((j) => (
                                        <SelectItem
                                            key={j.id}
                                            value={String(j.id)}
                                        >
                                            {j.nama}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            {errors.kompetensi_keahlian_id && (
                                <p className="mt-1 text-xs text-destructive">
                                    {errors.kompetensi_keahlian_id}
                                </p>
                            )}
                        </div>

                        <div>
                            <Label>Email</Label>
                            <Input
                                type="email"
                                value={form.email}
                                onChange={(e) =>
                                    setForm({ ...form, email: e.target.value })
                                }
                                placeholder="email@sekolah.id"
                            />
                            {errors.email && (
                                <p className="mt-1 text-xs text-destructive">
                                    {errors.email}
                                </p>
                            )}
                        </div>

                        <div className="grid grid-cols-2 gap-3">
                            <div>
                                <Label>Alamat</Label>
                                <Input
                                    value={form.alamat}
                                    onChange={(e) =>
                                        setForm({
                                            ...form,
                                            alamat: e.target.value,
                                        })
                                    }
                                    placeholder="Alamat lengkap"
                                />
                            </div>
                            <div>
                                <Label>No. Telepon</Label>
                                <Input
                                    value={form.no_telp}
                                    onChange={(e) =>
                                        setForm({
                                            ...form,
                                            no_telp: e.target.value,
                                        })
                                    }
                                    placeholder="08xx-xxxx-xxxx"
                                />
                            </div>
                        </div>

                        <div>
                            <Label>
                                Password{' '}
                                {modeTambah && (
                                    <span className="text-muted-foreground">
                                        (default: NIS)
                                    </span>
                                )}
                            </Label>
                            <Input
                                type="password"
                                value={form.password}
                                onChange={(e) =>
                                    setForm({
                                        ...form,
                                        password: e.target.value,
                                    })
                                }
                                placeholder={
                                    modeTambah
                                        ? 'Kosongkan untuk pakai NIS'
                                        : 'Kosongkan jika tidak diubah'
                                }
                            />
                        </div>
                    </div>

                    <DialogFooter>
                        <Button
                            variant="ghost"
                            onClick={() => setDialogBuka(false)}
                        >
                            Batal
                        </Button>
                        <Button
                            onClick={() => simpan()}
                            disabled={
                                menyimpan || (!modeTambah && !adaPerubahan)
                            }
                        >
                            {menyimpan ? 'Menyimpan...' : 'Simpan'}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}

KelolaSiswa.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: hubinDashboard() },
        { title: 'Kelola Siswa' },
    ],
};
