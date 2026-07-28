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

interface JurusanItem {
    id: number;
    nama: string;
    acronym: string | null;
}

interface Props {
    jurusan: { data: JurusanItem[]; meta: any };
}

export default function Index({ jurusan }: Props) {
    const [open, setOpen] = useState(false);
    const [edit, setEdit] = useState<JurusanItem | null>(null);
    const [form, setForm] = useState({ nama: '', acronym: '' });

    function openCreate() {
        setEdit(null);
        setForm({ nama: '', acronym: '' });
        setOpen(true);
    }

    function openEdit(j: JurusanItem) {
        setEdit(j);
        setForm({ nama: j.nama, acronym: j.acronym ?? '' });
        setOpen(true);
    }

    function submit() {
        if (edit) {
            router.put(`/hubin/kompetensi-keahlian/${edit.id}`, form);
        } else {
            router.post('/hubin/kompetensi-keahlian', form);
        }

        setOpen(false);
    }

    function hapus(id: number) {
        if (confirm('Yakin ingin menghapus jurusan ini?')) {
            router.delete(`/hubin/kompetensi-keahlian/${id}`);
        }
    }

    return (
        <>
            <Head title="Kelola Kompetensi Keahlian" />
            <div className="flex flex-col gap-4 p-4">
                <div className="flex items-center justify-between">
                    <h2 className="text-2xl font-semibold">
                        Kompetensi Keahlian
                    </h2>
                    <Button onClick={openCreate}>Tambah Jurusan</Button>
                </div>

                <div className="bg-surface rounded-lg border">
                    <table className="w-full text-sm">
                        <thead>
                            <tr className="border-b text-left text-muted-foreground">
                                <th className="p-3">Nama Jurusan</th>
                                <th className="p-3">Akronim</th>
                                <th className="p-3">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            {jurusan.data.map((j) => (
                                <tr
                                    key={j.id}
                                    className="border-b last:border-0"
                                >
                                    <td className="p-3 font-medium">
                                        {j.nama}
                                    </td>
                                    <td className="p-3 text-muted-foreground">
                                        {j.acronym}
                                    </td>
                                    <td className="p-3">
                                        <div className="flex gap-2">
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                onClick={() => openEdit(j)}
                                            >
                                                Edit
                                            </Button>
                                            <Button
                                                variant="destructive"
                                                size="sm"
                                                onClick={() => hapus(j.id)}
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
                                {edit ? 'Edit Jurusan' : 'Tambah Jurusan'}
                            </DialogTitle>
                            <DialogDescription>
                                Kelola kompetensi keahlian (jurusan).
                            </DialogDescription>
                        </DialogHeader>
                        <div className="flex flex-col gap-3">
                            <div>
                                <Label htmlFor="nama">Nama Jurusan</Label>
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
                                <Label htmlFor="acronym">Akronim</Label>
                                <Input
                                    id="acronym"
                                    value={form.acronym}
                                    onChange={(e) =>
                                        setForm({
                                            ...form,
                                            acronym: e.target.value,
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
        { title: 'Kompetensi Keahlian' },
    ],
};
