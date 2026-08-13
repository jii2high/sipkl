import { Head, router } from '@inertiajs/react';
import { Pencil, Plus, Trash2, UserMinus } from 'lucide-react';
import { useState } from 'react';
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
    DropdownMenuSeparator,
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

interface GuruItem {
    id: number;
    nip: string | null;
    jabatan: 'kaprog' | 'guru_pembimbing' | 'hubin';
    kompetensi_keahlian_id: number | null;
    user_id: number;
    user: { id: number; name: string; email: string };
    kompetensi_keahlian: { id: number; nama: string } | null;
}

interface JurusanItem {
    id: number;
    nama: string;
}

interface Props {
    guru: { data: GuruItem[]; links: any[]; last_page: number };
    jurusan: JurusanItem[];
    adaKaprog: boolean;
}

const LABEL_JABATAN: Record<string, string> = {
    kaprog: 'Kaprog',
    guru_pembimbing: 'Guru Pembimbing',
    hubin: 'Staf Hubin',
};

const formKosong = {
    nip: '',
    name: '',
    email: '',
    jabatan: '',
    kompetensi_keahlian_id: '',
    password: '',
};

export default function KelolaStaff({ guru, adaKaprog }: Props) {
    const [dialogBuka, setDialogBuka] = useState(false);
    const [modeTambah, setModeTambah] = useState(true);
    const [editTarget, setEditTarget] = useState<GuruItem | null>(null);
    const [form, setForm] = useState(formKosong);
    const [menyimpan, setMenyimpan] = useState(false);
    const [duplikat, setDuplikat] = useState<{ nama: string; nip: string } | null>(null);
    const [errors, setErrors] = useState<Record<string, string>>({});

    const kaprogWarning = form.jabatan === 'kaprog' && adaKaprog && (
        modeTambah || (editTarget && editTarget.jabatan !== 'kaprog')
    );

    function bukaTambah() {
        setModeTambah(true);
        setEditTarget(null);
        setForm(formKosong);
        setDuplikat(null);
        setErrors({});
        setDialogBuka(true);
    }

    function bukaEdit(g: GuruItem) {
        setModeTambah(false);
        setEditTarget(g);
        setForm({
            nip: g.nip ?? '',
            name: g.user.name,
            email: g.user.email,
            jabatan: g.jabatan,
            kompetensi_keahlian_id: String(g.kompetensi_keahlian_id ?? ''),
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
            router.post('/hubin/kelola-staff', payload, { onSuccess, onError, onFinish: () => setMenyimpan(false) });
        } else {
            router.put(`/hubin/kelola-staff/${editTarget!.id}`, payload, { onSuccess, onError, onFinish: () => setMenyimpan(false) });
        }
    }

    function hapus(g: GuruItem) {
        if (confirm(`Hapus akun ${g.user.name}? (masuk recycle bin)`)) {
            router.delete(`/hubin/kelola-staff/${g.id}`);
        }
    }

    function nonaktifkanKaprog(g: GuruItem) {
        if (confirm(`Nonaktifkan ${g.user.name} dari jabatan Kaprog? Role otomatis jadi Guru Pembimbing.`)) {
            router.patch(`/hubin/kelola-staff/${g.id}/nonaktifkan`);
        }
    }

    const adaPerubahan = modeTambah || (
        editTarget !== null && (
            form.name !== editTarget.user.name ||
            form.email !== editTarget.user.email ||
            form.nip !== (editTarget.nip ?? '') ||
            form.jabatan !== editTarget.jabatan ||
            form.kompetensi_keahlian_id !== String(editTarget.kompetensi_keahlian_id ?? '') ||
            form.password !== ''
        )
    );

    return (
        <>
            <Head title="Kelola Staff" />
            <div className="flex flex-col gap-4 p-4">
                <div className="flex items-center justify-between">
                    <h2 className="text-2xl font-semibold">Kelola Staff</h2>
                    <Button onClick={bukaTambah}>
                        <Plus className="mr-1 size-4" /> Tambah Staff
                    </Button>
                </div>

                <SearchFilterBar
                    baseRoute="/hubin/kelola-staff"
                    searchPlaceholder="Cari nama staff..."
                    filters={[
                        {
                            key: 'jabatan',
                            label: 'Jabatan',
                            options: [
                                { label: 'Kaprog', value: 'kaprog' },
                                { label: 'Guru Pembimbing', value: 'guru_pembimbing' },
                                { label: 'Staf Hubin', value: 'hubin' },
                            ],
                        },
                    ]}
                />

                <div className="bg-surface overflow-hidden rounded-lg border">
                    <table className="w-full text-sm">
                        <thead className="text-left text-muted-foreground">
                            <tr className="border-b">
                                <th className="p-3">NIP</th>
                                <th className="p-3">Nama</th>
                                <th className="p-3">Jabatan</th>
                                <th className="p-3">Email</th>
                                <th className="p-3">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            {guru.data.length === 0 && (
                                <tr>
                                    <td colSpan={5} className="p-6 text-center text-muted-foreground">
                                        Tidak ada data staff.
                                    </td>
                                </tr>
                            )}
                            {guru.data.map((g) => (
                                <tr key={g.id} className="border-b last:border-0">
                                    <td className="p-3 font-mono text-sm">{g.nip ?? '-'}</td>
                                    <td className="p-3 font-medium">{g.user.name}</td>
                                    <td className="p-3">
                                        <Badge
                                            variant={g.jabatan === 'kaprog' ? 'default' : 'secondary'}
                                        >
                                            {LABEL_JABATAN[g.jabatan]}
                                        </Badge>
                                    </td>
                                    <td className="p-3 text-muted-foreground">{g.user.email}</td>
                                    <td className="p-3">
                                        <DropdownMenu>
                                            <DropdownMenuTrigger asChild>
                                                <Button variant="ghost" size="sm">
                                                    •••
                                                </Button>
                                            </DropdownMenuTrigger>
                                            <DropdownMenuContent align="end">
                                                <DropdownMenuItem onClick={() => bukaEdit(g)}>
                                                    <Pencil className="mr-2 size-4" /> Edit
                                                </DropdownMenuItem>
                                                {g.jabatan === 'kaprog' && (
                                                    <DropdownMenuItem onClick={() => nonaktifkanKaprog(g)}>
                                                        <UserMinus className="mr-2 size-4" /> Nonaktifkan Kaprog
                                                    </DropdownMenuItem>
                                                )}
                                                <DropdownMenuSeparator />
                                                <DropdownMenuItem
                                                    className="text-destructive"
                                                    onClick={() => hapus(g)}
                                                >
                                                    <Trash2 className="mr-2 size-4" /> Hapus
                                                </DropdownMenuItem>
                                            </DropdownMenuContent>
                                        </DropdownMenu>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>

                {guru.last_page > 1 && (
                    <div className="flex justify-center gap-2">
                        {guru.links
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

            {/* Dialog Tambah / Edit Staff */}
            <Dialog open={dialogBuka} onOpenChange={setDialogBuka}>
                <DialogContent className="max-w-lg">
                    <DialogHeader>
                        <DialogTitle>{modeTambah ? 'Tambah Staff' : 'Edit Staff'}</DialogTitle>
                    </DialogHeader>

                    {/* Alert kaprog warning */}
                    {kaprogWarning && (
                        <div className="flex items-start gap-2 rounded-md border border-destructive/30 bg-destructive/10 p-3 text-sm text-destructive">
                            <span>⚠️</span>
                            <span>Sudah ada Kaprog aktif. Menambah Kaprog baru akan ada dua Kaprog sekaligus.</span>
                        </div>
                    )}

                    {/* Alert duplikat */}
                    {duplikat && (
                        <div className="rounded-md border border-destructive/40 bg-destructive/10 p-3 text-sm text-destructive">
                            Data telah dimiliki oleh{' '}
                            <strong>
                                {duplikat.nama}; NIP {duplikat.nip}
                            </strong>
                            . Yakin ingin menimpa?
                            <div className="mt-2 flex gap-2">
                                <Button size="sm" variant="destructive" onClick={() => simpan(true)}>
                                    Ya, Timpa
                                </Button>
                                <Button size="sm" variant="ghost" onClick={() => setDuplikat(null)}>
                                    Batal
                                </Button>
                            </div>
                        </div>
                    )}

                    <div className="grid gap-3">
                        <div className="grid grid-cols-2 gap-3">
                            <div>
                                <Label>NIP</Label>
                                <Input
                                    value={form.nip}
                                    onChange={(e) => setForm({ ...form, nip: e.target.value })}
                                    placeholder="Nomor Induk Pegawai"
                                />
                                {errors.nip && <p className="mt-1 text-xs text-destructive">{errors.nip}</p>}
                            </div>
                            <div>
                                <Label>Nama Lengkap</Label>
                                <Input
                                    value={form.name}
                                    onChange={(e) => setForm({ ...form, name: e.target.value })}
                                    placeholder="Nama staff"
                                />
                                {errors.name && <p className="mt-1 text-xs text-destructive">{errors.name}</p>}
                            </div>
                        </div>

                        <div>
                            <Label>Jabatan</Label>
                            <Select
                                value={form.jabatan}
                                onValueChange={(v) => setForm({ ...form, jabatan: v })}
                            >
                                <SelectTrigger>
                                    <SelectValue placeholder="Pilih jabatan" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="kaprog">Kaprog</SelectItem>
                                    <SelectItem value="guru_pembimbing">Guru Pembimbing</SelectItem>
                                    <SelectItem value="hubin">Staf Hubin</SelectItem>
                                </SelectContent>
                            </Select>
                            {errors.jabatan && <p className="mt-1 text-xs text-destructive">{errors.jabatan}</p>}
                        </div>

                        <div>
                            <Label>Email</Label>
                            <Input
                                type="email"
                                value={form.email}
                                onChange={(e) => setForm({ ...form, email: e.target.value })}
                                placeholder="email@sekolah.id"
                            />
                            {errors.email && <p className="mt-1 text-xs text-destructive">{errors.email}</p>}
                        </div>

                        <div>
                            <Label>
                                Password{' '}
                                {modeTambah && (
                                    <span className="text-muted-foreground">(default: NIP)</span>
                                )}
                            </Label>
                            <Input
                                type="password"
                                value={form.password}
                                onChange={(e) => setForm({ ...form, password: e.target.value })}
                                placeholder={modeTambah ? 'Kosongkan untuk pakai NIP' : 'Kosongkan jika tidak diubah'}
                            />
                        </div>
                    </div>

                    <DialogFooter>
                        <Button variant="ghost" onClick={() => setDialogBuka(false)}>
                            Batal
                        </Button>
                        <Button
                            onClick={() => simpan()}
                            disabled={menyimpan || (!modeTambah && !adaPerubahan)}
                        >
                            {menyimpan ? 'Menyimpan...' : 'Simpan'}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}

KelolaStaff.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: hubinDashboard() },
        { title: 'Kelola Staff' },
    ],
};
