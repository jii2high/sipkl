import { Head, router } from '@inertiajs/react';
import { Send, Trash2 } from 'lucide-react';
import { useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
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
import { dashboard as kaprogDashboard } from '@/routes/kaprog';

interface SuratTugasItem {
    id: number;
    status: string;
    lingkup: string;
    nomor_surat: string | null;
    tanggal_terbit: string | null;
    berlaku_hingga: string | null;
    periode: { id: number; nama: string };
    kompetensi_keahlian: { id: number; nama: string } | null;
    requested_by: string | null;
    diterbitkan_by: string | null;
}

interface Props {
    drafts: SuratTugasItem[];
    terbit: SuratTugasItem[];
    periodes: { id: number; nama: string }[];
}

export default function Monitoring({ drafts, terbit, periodes }: Props) {
    const [open, setOpen] = useState(false);
    const [form, setForm] = useState({
        periode_id: '',
        berlaku_hingga: '',
    });

    function submit() {
        router.post('/kaprog/monitoring', form);
        setOpen(false);
    }

    function terbitkan(id: number) {
        if (
            confirm(
                'Terbitkan surat tugas ini? Nomor surat akan dibuat otomatis.',
            )
        ) {
            router.post(`/kaprog/monitoring/${id}/terbit`);
        }
    }

    function hapusDraft(id: number) {
        if (confirm('Batalkan draft ini?')) {
            router.delete(`/kaprog/monitoring/${id}`);
        }
    }

    return (
        <>
            <Head title="Monitoring PKL" />
            <div className="flex flex-col gap-6 p-4">
                <div className="flex items-center justify-between">
                    <div>
                        <h2 className="text-2xl font-semibold">
                            Monitoring PKL
                        </h2>
                        <p className="text-sm text-muted-foreground">
                            Buat draft Surat Tugas Monitoring untuk jurusan
                            Anda, lalu Hubin/Anda menerbitkannya.
                        </p>
                    </div>
                    <Button onClick={() => setOpen(true)}>
                        + Buat Draft Surat Tugas
                    </Button>
                </div>

                <section>
                    <h3 className="mb-2 font-semibold">
                        Draft Menunggu Terbit
                    </h3>
                    {drafts.length === 0 ? (
                        <p className="text-sm text-muted-foreground">
                            Tidak ada draft.
                        </p>
                    ) : (
                        <div className="space-y-2">
                            {drafts.map((d) => (
                                <div
                                    key={d.id}
                                    className="bg-surface flex items-center justify-between rounded-lg border p-3"
                                >
                                    <div>
                                        <p className="font-medium">
                                            {d.periode.nama}
                                        </p>
                                        <p className="text-sm text-muted-foreground">
                                            Berlaku s.d.{' '}
                                            {d.berlaku_hingga ?? '-'}
                                        </p>
                                    </div>
                                    <div className="flex gap-2">
                                        <Button
                                            size="sm"
                                            onClick={() => terbitkan(d.id)}
                                        >
                                            <Send className="mr-1 size-4" />
                                            Terbitkan
                                        </Button>
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            className="text-destructive"
                                            onClick={() => hapusDraft(d.id)}
                                        >
                                            <Trash2 className="size-4" />
                                        </Button>
                                    </div>
                                </div>
                            ))}
                        </div>
                    )}
                </section>

                <section>
                    <h3 className="mb-2 font-semibold">
                        Ringkasan Surat Tugas Terbit
                    </h3>
                    {terbit.length === 0 ? (
                        <p className="text-sm text-muted-foreground">
                            Belum ada surat tugas terbit.
                        </p>
                    ) : (
                        <div className="overflow-hidden rounded-lg border">
                            <table className="w-full text-sm">
                                <thead className="bg-surface text-left text-muted-foreground">
                                    <tr>
                                        <th className="p-3">Nomor</th>
                                        <th className="p-3">Periode</th>
                                        <th className="p-3">Lingkup</th>
                                        <th className="p-3">Status</th>
                                        <th className="p-3">Terbit</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {terbit.map((t) => (
                                        <tr
                                            key={t.id}
                                            className="border-b last:border-0"
                                        >
                                            <td className="p-3">
                                                {t.nomor_surat ?? '-'}
                                            </td>
                                            <td className="p-3">
                                                {t.periode.nama}
                                            </td>
                                            <td className="p-3">
                                                {t.lingkup === 'global'
                                                    ? 'Semua jurusan'
                                                    : t.kompetensi_keahlian
                                                          ?.nama}
                                            </td>
                                            <td className="p-3">
                                                <Badge
                                                    variant={
                                                        t.status === 'aktif'
                                                            ? 'default'
                                                            : 'secondary'
                                                    }
                                                >
                                                    {t.status}
                                                </Badge>
                                            </td>
                                            <td className="p-3 text-muted-foreground">
                                                {t.tanggal_terbit ?? '-'}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </section>
            </div>

            <Dialog open={open} onOpenChange={setOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Buat Draft Surat Tugas</DialogTitle>
                        <DialogDescription>
                            Scoped ke jurusan Anda. Hubin menerbitkannya.
                        </DialogDescription>
                    </DialogHeader>
                    <div className="flex flex-col gap-3">
                        <div>
                            <Label>Periode</Label>
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
                        <div>
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
                        <Button onClick={submit}>Simpan Draft</Button>
                    </div>
                </DialogContent>
            </Dialog>
        </>
    );
}

Monitoring.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: kaprogDashboard() },
        { title: 'Monitoring PKL' },
    ],
};
