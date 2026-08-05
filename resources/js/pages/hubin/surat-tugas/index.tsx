import { Head, router, useForm } from '@inertiajs/react';
import { Download, FileCheck2, Power } from 'lucide-react';
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
import {
    download as suratTugasDownload,
    index as suratTugasIndex,
    nonaktif as suratTugasNonaktif,
    store as suratTugasStore,
} from '@/routes/hubin/surat-tugas';

export default function SuratTugas({
    suratTugass,
    periodes,
    kompetensiKeahlians,
}: any) {
    const [open, setOpen] = useState(false);
    const { data, setData, post, errors, processing } = useForm({
        periode_id: '',
        lingkup: 'global',
        kompetensi_keahlian_id: '',
        berlaku_hingga: '',
    });

    function submit(e: React.FormEvent) {
        e.preventDefault();

        post(suratTugasStore().url, {
            onSuccess: () => {
                setOpen(false);
                setData('berlaku_hingga', '');
            },
        });
    }

    function nonaktifkan(id: number) {
        if (confirm('Yakin ingin menonaktifkan Surat Tugas ini?')) {
            router.post(suratTugasNonaktif({ suratTugas: id }).url);
        }
    }

    return (
        <>
            <Head title="Surat Tugas Monitoring" />
            <div className="flex flex-col gap-4 p-4">
                <div className="flex items-center justify-between">
                    <h2 className="text-2xl font-semibold">
                        Surat Tugas Monitoring
                    </h2>
                    <Button onClick={() => setOpen(true)}>
                        Terbit Surat Baru
                    </Button>
                </div>

                {suratTugass?.length === 0 && (
                    <div className="bg-surface rounded-lg border p-8 text-center text-muted-foreground">
                        Belum ada Surat Tugas diterbitkan.
                    </div>
                )}

                {suratTugass?.length > 0 && (
                    <div className="bg-surface rounded-lg border">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="border-b text-left text-muted-foreground">
                                    <th className="p-3">Nomor Surat</th>
                                    <th className="p-3">Periode</th>
                                    <th className="p-3">Lingkup</th>
                                    <th className="p-3">Berlaku S/d</th>
                                    <th className="p-3">Status</th>
                                    <th className="p-3">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                {suratTugass?.map((s: any) => (
                                    <tr
                                        key={s.id}
                                        className="border-b last:border-0"
                                    >
                                        <td className="p-3 font-medium">
                                            {s.nomor_surat}
                                        </td>
                                        <td className="p-3">
                                            {s.periode?.nama}
                                        </td>
                                        <td className="p-3">
                                            {s.lingkup === 'global'
                                                ? 'Global'
                                                : s.kompetensi_keahlian?.nama}
                                        </td>
                                        <td className="p-3">
                                            {s.berlaku_hingga}
                                        </td>
                                        <td className="p-3">
                                            <Badge
                                                className={
                                                    s.status === 'aktif'
                                                        ? 'bg-green-100 text-green-800'
                                                        : 'bg-muted text-muted-foreground'
                                                }
                                            >
                                                {s.status}
                                            </Badge>
                                        </td>
                                        <td className="p-3">
                                            <div className="flex gap-2">
                                                {s.file && (
                                                    <Button
                                                        variant="outline"
                                                        size="sm"
                                                        asChild
                                                    >
                                                        <a
                                                            href={
                                                                suratTugasDownload(
                                                                    {
                                                                        suratTugas:
                                                                            s.id,
                                                                    },
                                                                ).url
                                                            }
                                                            target="_blank"
                                                            rel="noopener noreferrer"
                                                        >
                                                            <Download className="mr-1 size-4" />
                                                            PDF
                                                        </a>
                                                    </Button>
                                                )}
                                                {s.status === 'aktif' && (
                                                    <Button
                                                        variant="destructive"
                                                        size="sm"
                                                        onClick={() =>
                                                            nonaktifkan(s.id)
                                                        }
                                                    >
                                                        <Power className="mr-1 size-4" />
                                                        Nonaktifkan
                                                    </Button>
                                                )}
                                            </div>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </div>

            <Dialog open={open} onOpenChange={setOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Terbitkan Surat Tugas</DialogTitle>
                        <DialogDescription>
                            Terbitkan Surat Tugas Monitoring untuk guru
                            pembimbing.
                        </DialogDescription>
                    </DialogHeader>

                    <form onSubmit={submit} className="flex flex-col gap-3">
                        <div className="space-y-2">
                            <Label htmlFor="periode_id">Periode PKL</Label>
                            <Select
                                value={data.periode_id}
                                onValueChange={(v) => setData('periode_id', v)}
                            >
                                <SelectTrigger>
                                    <SelectValue placeholder="Pilih periode" />
                                </SelectTrigger>
                                <SelectContent>
                                    {periodes?.map((p: any) => (
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
                                <p className="text-sm text-red-500">
                                    {errors.periode_id}
                                </p>
                            )}
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="lingkup">Lingkup</Label>
                            <Select
                                value={data.lingkup}
                                onValueChange={(v) => setData('lingkup', v)}
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

                        {data.lingkup === 'scoped' && (
                            <div className="space-y-2">
                                <Label htmlFor="kompetensi_keahlian_id">
                                    Kompetensi Keahlian
                                </Label>
                                <Select
                                    value={data.kompetensi_keahlian_id}
                                    onValueChange={(v) =>
                                        setData('kompetensi_keahlian_id', v)
                                    }
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="Pilih jurusan" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {kompetensiKeahlians?.map((kk: any) => (
                                            <SelectItem
                                                key={kk.id}
                                                value={String(kk.id)}
                                            >
                                                {kk.nama}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                {errors.kompetensi_keahlian_id && (
                                    <p className="text-sm text-red-500">
                                        {errors.kompetensi_keahlian_id}
                                    </p>
                                )}
                            </div>
                        )}

                        <div className="space-y-2">
                            <Label htmlFor="berlaku_hingga">
                                Berlaku Hingga
                            </Label>
                            <Input
                                id="berlaku_hingga"
                                type="date"
                                required
                                value={data.berlaku_hingga}
                                onChange={(e) =>
                                    setData('berlaku_hingga', e.target.value)
                                }
                            />
                            {errors.berlaku_hingga && (
                                <p className="text-sm text-red-500">
                                    {errors.berlaku_hingga}
                                </p>
                            )}
                        </div>

                        <Button type="submit" disabled={processing}>
                            <FileCheck2 className="mr-1 size-4" />
                            {processing ? 'Menerbitkan...' : 'Terbitkan'}
                        </Button>
                    </form>
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
