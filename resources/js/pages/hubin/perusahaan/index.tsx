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

interface PerusahaanItem {
    id: number;
    nama: string;
    alamat: string | null;
    telp: string | null;
    kuota_saran: number;
}

interface Props {
    perusahaan: { data: PerusahaanItem[]; meta: any };
}

export default function Index({ perusahaan }: Props) {
    const [open, setOpen] = useState(false);
    const [edit, setEdit] = useState<PerusahaanItem | null>(null);
    const [form, setForm] = useState({
        nama: '',
        alamat: '',
        telp: '',
        kuota_saran: 6,
    });

    function openCreate() {
        setEdit(null);
        setForm({ nama: '', alamat: '', telp: '', kuota_saran: 6 });
        setOpen(true);
    }

    function openEdit(p: PerusahaanItem) {
        setEdit(p);
        setForm({
            nama: p.nama,
            alamat: p.alamat ?? '',
            telp: p.telp ?? '',
            kuota_saran: p.kuota_saran,
        });
        setOpen(true);
    }

    function submit() {
        if (edit) {
            router.put(`/hubin/perusahaan/${edit.id}`, form);
        } else {
            router.post('/hubin/perusahaan', form);
        }

        setOpen(false);
    }

    function hapus(id: number) {
        if (confirm('Yakin ingin menghapus perusahaan ini?')) {
            router.delete(`/hubin/perusahaan/${id}`);
        }
    }

    return (
        <>
            <Head title="Kelola Perusahaan" />
            <div className="flex flex-col gap-4 p-4">
                <div className="flex items-center justify-between">
                    <h2 className="text-2xl font-semibold">Perusahaan</h2>
                    <Button onClick={openCreate}>Tambah Perusahaan</Button>
                </div>

                <div className="bg-surface rounded-lg border">
                    <table className="w-full text-sm">
                        <thead>
                            <tr className="border-b text-left text-muted-foreground">
                                <th className="p-3">Nama</th>
                                <th className="p-3">Alamat</th>
                                <th className="p-3">Telepon</th>
                                <th className="p-3">Kuota</th>
                                <th className="p-3">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            {perusahaan.data.map((p) => (
                                <tr
                                    key={p.id}
                                    className="border-b last:border-0"
                                >
                                    <td className="p-3 font-medium">
                                        {p.nama}
                                    </td>
                                    <td className="p-3 text-muted-foreground">
                                        {p.alamat}
                                    </td>
                                    <td className="p-3 text-muted-foreground">
                                        {p.telp}
                                    </td>
                                    <td className="p-3">{p.kuota_saran}</td>
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
                                {edit ? 'Edit Perusahaan' : 'Tambah Perusahaan'}
                            </DialogTitle>
                            <DialogDescription>
                                Isi data perusahaan mitra PKL.
                            </DialogDescription>
                        </DialogHeader>
                        <div className="flex flex-col gap-3">
                            <div>
                                <Label htmlFor="nama">Nama Perusahaan</Label>
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
                                <Label htmlFor="alamat">Alamat</Label>
                                <Input
                                    id="alamat"
                                    value={form.alamat}
                                    onChange={(e) =>
                                        setForm({
                                            ...form,
                                            alamat: e.target.value,
                                        })
                                    }
                                />
                            </div>
                            <div>
                                <Label htmlFor="telp">Telepon</Label>
                                <Input
                                    id="telp"
                                    value={form.telp}
                                    onChange={(e) =>
                                        setForm({
                                            ...form,
                                            telp: e.target.value,
                                        })
                                    }
                                />
                            </div>
                            <div>
                                <Label htmlFor="kuota_saran">Kuota Saran</Label>
                                <Input
                                    id="kuota_saran"
                                    type="number"
                                    value={form.kuota_saran}
                                    onChange={(e) =>
                                        setForm({
                                            ...form,
                                            kuota_saran: +e.target.value,
                                        })
                                    }
                                />
                            </div>
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
        { title: 'Perusahaan' },
    ],
};
