import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { dashboard as hubinDashboard } from '@/routes/hubin';

interface PeriodeItem {
    id: number;
    nama: string;
    tanggal_mulai: string;
    tanggal_selesai: string;
    status_aktif: boolean;
}

interface Props {
    periode: { data: PeriodeItem[]; meta: any };
}

export default function Index({ periode }: Props) {
    const [open, setOpen] = useState(false);
    const [edit, setEdit] = useState<PeriodeItem | null>(null);
    const [form, setForm] = useState({
        nama: '',
        tanggal_mulai: '',
        tanggal_selesai: '',
        status_aktif: false,
    });

    function openCreate() {
        setEdit(null);
        setForm({
            nama: '',
            tanggal_mulai: '',
            tanggal_selesai: '',
            status_aktif: false,
        });
        setOpen(true);
    }

    function openEdit(p: PeriodeItem) {
        setEdit(p);
        setForm({
            nama: p.nama,
            tanggal_mulai: p.tanggal_mulai,
            tanggal_selesai: p.tanggal_selesai,
            status_aktif: p.status_aktif,
        });
        setOpen(true);
    }

    function submit() {
        if (edit) {
            router.put(`/hubin/periode-pkl/${edit.id}`, form);
        } else {
            router.post('/hubin/periode-pkl', form);
        }

        setOpen(false);
    }

    function hapus(id: number) {
        if (confirm('Yakin ingin menghapus periode ini?')) {
            router.delete(`/hubin/periode-pkl/${id}`);
        }
    }

    return (
        <>
            <Head title="Kelola Periode PKL" />
            <div className="flex flex-col gap-4 p-4">
                <div className="flex items-center justify-between">
                    <h2 className="text-2xl font-semibold">Periode PKL</h2>
                    <Button onClick={openCreate}>Tambah Periode</Button>
                </div>

                <div className="bg-surface rounded-lg border">
                    <table className="w-full text-sm">
                        <thead>
                            <tr className="border-b text-left text-muted-foreground">
                                <th className="p-3">Nama</th>
                                <th className="p-3">Tanggal Mulai</th>
                                <th className="p-3">Tanggal Selesai</th>
                                <th className="p-3">Status</th>
                                <th className="p-3">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            {periode.data.map((p) => (
                                <tr
                                    key={p.id}
                                    className="border-b last:border-0"
                                >
                                    <td className="p-3 font-medium">
                                        {p.nama}
                                    </td>
                                    <td className="p-3 text-muted-foreground">
                                        {p.tanggal_mulai}
                                    </td>
                                    <td className="p-3 text-muted-foreground">
                                        {p.tanggal_selesai}
                                    </td>
                                    <td className="p-3">
                                        {p.status_aktif
                                            ? 'Aktif'
                                            : 'Tidak Aktif'}
                                    </td>
                                    <td className="p-3">
                                        <div className="flex gap-2">
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                onClick={() => openEdit(p)}
                                            >
                                                Edit
                                            </Button>
                                            <Button
                                                variant="destructive"
                                                size="sm"
                                                onClick={() => hapus(p.id)}
                                            >
                                                Hapus
                                            </Button>
                                        </div>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>

                <Dialog open={open} onOpenChange={setOpen}>
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle>
                                {edit
                                    ? 'Edit Periode PKL'
                                    : 'Tambah Periode PKL'}
                            </DialogTitle>
                            <DialogDescription>
                                Atur periode pelaksanaan PKL.
                            </DialogDescription>
                        </DialogHeader>
                        <div className="flex flex-col gap-3">
                            <div>
                                <Label htmlFor="nama">Nama Periode</Label>
                                <Input
                                    id="nama"
                                    value={form.nama}
                                    onChange={(e) =>
                                        setForm({
                                            ...form,
                                            nama: e.target.value,
                                        })
                                    }
                                />
                            </div>
                            <div>
                                <Label htmlFor="tanggal_mulai">
                                    Tanggal Mulai
                                </Label>
                                <Input
                                    id="tanggal_mulai"
                                    type="date"
                                    value={form.tanggal_mulai}
                                    onChange={(e) =>
                                        setForm({
                                            ...form,
                                            tanggal_mulai: e.target.value,
                                        })
                                    }
                                />
                            </div>
                            <div>
                                <Label htmlFor="tanggal_selesai">
                                    Tanggal Selesai
                                </Label>
                                <Input
                                    id="tanggal_selesai"
                                    type="date"
                                    value={form.tanggal_selesai}
                                    onChange={(e) =>
                                        setForm({
                                            ...form,
                                            tanggal_selesai: e.target.value,
                                        })
                                    }
                                />
                            </div>
                            <label className="flex items-center gap-2">
                                <input
                                    type="checkbox"
                                    checked={form.status_aktif}
                                    onChange={(e) =>
                                        setForm({
                                            ...form,
                                            status_aktif: e.target.checked,
                                        })
                                    }
                                />
                                Periode Aktif
                            </label>
                            <Button onClick={submit}>
                                {edit ? 'Simpan' : 'Tambah'}
                            </Button>
                        </div>
                    </DialogContent>
                </Dialog>
            </div>
        </>
    );
}

Index.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: hubinDashboard() },
        { title: 'Periode PKL' },
    ],
};
