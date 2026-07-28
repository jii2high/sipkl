import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import { Badge } from '@/components/ui/badge';
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
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { dashboard as hubinDashboard } from '@/routes/hubin';

interface SectionItem {
    id: number;
    nomor_urut: number;
    deskripsi: string;
}

interface IndikatorItem {
    id: number;
    kategori: string;
    kompetensi_keahlian_id: number | null;
    nomor_urut: number;
    deskripsi: string;
    sections: SectionItem[];
    kompetensi_keahlian: { id: number; nama: string } | null;
}

interface JurusanOption {
    id: number;
    nama: string;
}

interface Props {
    indikator: { data: IndikatorItem[]; meta: any };
    jurusan: JurusanOption[];
}

export default function Index({ indikator, jurusan }: Props) {
    const [open, setOpen] = useState(false);
    const [edit, setEdit] = useState<IndikatorItem | null>(null);
    const [form, setForm] = useState({
        kategori: 'umum',
        kompetensi_keahlian_id: '',
        nomor_urut: 1,
        deskripsi: '',
    });

    const [sectionOpen, setSectionOpen] = useState(false);
    const [sectionIndikator, setSectionIndikator] =
        useState<IndikatorItem | null>(null);
    const [sectionForm, setSectionForm] = useState({
        nomor_urut: 1,
        deskripsi: '',
    });

    function openCreate() {
        setEdit(null);
        setForm({
            kategori: 'umum',
            kompetensi_keahlian_id: '',
            nomor_urut: 1,
            deskripsi: '',
        });
        setOpen(true);
    }

    function openEdit(i: IndikatorItem) {
        setEdit(i);
        setForm({
            kategori: i.kategori,
            kompetensi_keahlian_id: i.kompetensi_keahlian_id?.toString() ?? '',
            nomor_urut: i.nomor_urut,
            deskripsi: i.deskripsi,
        });
        setOpen(true);
    }

    function submit() {
        const payload = {
            ...form,
            kompetensi_keahlian_id: form.kompetensi_keahlian_id
                ? +form.kompetensi_keahlian_id
                : null,
        };

        if (edit) {
            router.put(`/hubin/indikator-penilaian/${edit.id}`, payload);
        } else {
            router.post('/hubin/indikator-penilaian', payload);
        }

        setOpen(false);
    }

    function hapus(id: number) {
        if (confirm('Yakin ingin menghapus indikator ini?')) {
            router.delete(`/hubin/indikator-penilaian/${id}`);
        }
    }

    function openSection(i: IndikatorItem) {
        setSectionIndikator(i);
        setSectionForm({
            nomor_urut: (i.sections?.length ?? 0) + 1,
            deskripsi: '',
        });
        setSectionOpen(true);
    }

    function submitSection() {
        if (sectionIndikator) {
            router.post(
                `/hubin/indikator-penilaian/${sectionIndikator.id}/section`,
                sectionForm,
            );
            setSectionOpen(false);
        }
    }

    function hapusSection(id: number) {
        if (confirm('Yakin ingin menghapus section ini?')) {
            router.delete(`/hubin/indikator-penilaian/section/${id}`);
        }
    }

    return (
        <>
            <Head title="Kelola Indikator Penilaian" />
            <div className="flex flex-col gap-4 p-4">
                <div className="flex items-center justify-between">
                    <h2 className="text-2xl font-semibold">
                        Indikator Penilaian
                    </h2>
                    <Button onClick={openCreate}>Tambah Indikator</Button>
                </div>

                <div className="bg-surface rounded-lg border">
                    <table className="w-full text-sm">
                        <thead>
                            <tr className="border-b text-left text-muted-foreground">
                                <th className="p-3">No</th>
                                <th className="p-3">Deskripsi</th>
                                <th className="p-3">Kategori</th>
                                <th className="p-3">Jurusan</th>
                                <th className="p-3">Section</th>
                                <th className="p-3">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            {indikator.data.map((i) => (
                                <tr
                                    key={i.id}
                                    className="border-b last:border-0"
                                >
                                    <td className="p-3">{i.nomor_urut}</td>
                                    <td className="p-3 font-medium">
                                        {i.deskripsi}
                                    </td>
                                    <td className="p-3">
                                        <Badge
                                            variant={
                                                i.kategori === 'umum'
                                                    ? 'secondary'
                                                    : 'default'
                                            }
                                        >
                                            {i.kategori === 'umum'
                                                ? 'Umum'
                                                : 'Teknis'}
                                        </Badge>
                                    </td>
                                    <td className="p-3 text-muted-foreground">
                                        {i.kompetensi_keahlian?.nama ?? '-'}
                                    </td>
                                    <td className="p-3">
                                        {i.sections?.length > 0 ? (
                                            <ul className="list-inside list-disc text-xs">
                                                {i.sections.map((s) => (
                                                    <li
                                                        key={s.id}
                                                        className="flex items-center gap-1"
                                                    >
                                                        {s.deskripsi}
                                                        <button
                                                            className="text-destructive hover:underline"
                                                            onClick={() =>
                                                                hapusSection(
                                                                    s.id,
                                                                )
                                                            }
                                                        >
                                                            hapus
                                                        </button>
                                                    </li>
                                                ))}
                                            </ul>
                                        ) : (
                                            <span className="text-xs text-muted-foreground">
                                                Tidak ada section
                                            </span>
                                        )}
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            onClick={() => openSection(i)}
                                        >
                                            + Section
                                        </Button>
                                    </td>
                                    <td className="p-3">
                                        <div className="flex gap-2">
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                onClick={() => openEdit(i)}
                                            >
                                                Edit
                                            </Button>
                                            <Button
                                                variant="destructive"
                                                size="sm"
                                                onClick={() => hapus(i.id)}
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
                                {edit ? 'Edit Indikator' : 'Tambah Indikator'}
                            </DialogTitle>
                            <DialogDescription>
                                Indikator penilaian PKL.
                            </DialogDescription>
                        </DialogHeader>
                        <div className="flex flex-col gap-3">
                            <div>
                                <Label htmlFor="deskripsi">Deskripsi</Label>
                                <Input
                                    id="deskripsi"
                                    value={form.deskripsi}
                                    onChange={(e) =>
                                        setForm({
                                            ...form,
                                            deskripsi: e.target.value,
                                        })
                                    }
                                />
                            </div>
                            <div>
                                <Label htmlFor="kategori">Kategori</Label>
                                <Select
                                    value={form.kategori}
                                    onValueChange={(v) =>
                                        setForm({ ...form, kategori: v })
                                    }
                                >
                                    <SelectTrigger>
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="umum">
                                            Umum
                                        </SelectItem>
                                        <SelectItem value="teknis_kejuruan">
                                            Teknis Kejuruan
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div>
                                <Label htmlFor="jurusan">
                                    Jurusan (kosongkan jika Umum)
                                </Label>
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
                                                value={j.id.toString()}
                                            >
                                                {j.nama}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                            <div>
                                <Label htmlFor="nomor_urut">Nomor Urut</Label>
                                <Input
                                    id="nomor_urut"
                                    type="number"
                                    value={form.nomor_urut}
                                    onChange={(e) =>
                                        setForm({
                                            ...form,
                                            nomor_urut: +e.target.value,
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

                <Dialog open={sectionOpen} onOpenChange={setSectionOpen}>
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle>Tambah Section</DialogTitle>
                            <DialogDescription>
                                Sub-indikator untuk{' '}
                                {sectionIndikator?.deskripsi}
                            </DialogDescription>
                        </DialogHeader>
                        <div className="flex flex-col gap-3">
                            <div>
                                <Label htmlFor="s_deskripsi">
                                    Deskripsi Section
                                </Label>
                                <Input
                                    id="s_deskripsi"
                                    value={sectionForm.deskripsi}
                                    onChange={(e) =>
                                        setSectionForm({
                                            ...sectionForm,
                                            deskripsi: e.target.value,
                                        })
                                    }
                                />
                            </div>
                            <div>
                                <Label htmlFor="s_nomor">Nomor Urut</Label>
                                <Input
                                    id="s_nomor"
                                    type="number"
                                    value={sectionForm.nomor_urut}
                                    onChange={(e) =>
                                        setSectionForm({
                                            ...sectionForm,
                                            nomor_urut: +e.target.value,
                                        })
                                    }
                                />
                            </div>
                            <Button onClick={submitSection}>Tambah</Button>
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
        { title: 'Indikator Penilaian' },
    ],
};
