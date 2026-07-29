import { Head, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import SearchFilterBar from '@/components/search-filter-bar';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

export default function PeriodePklIndex() {
    const { periodes, filters } = usePage().props as any;
    const [form, setForm] = useState({
        nama: '',
        tanggal_mulai: '',
        tanggal_selesai: '',
        status_aktif: false,
    });
    const [editing, setEditing] = useState<any>(null);

    function resetForm() {
        setForm({
            nama: '',
            tanggal_mulai: '',
            tanggal_selesai: '',
            status_aktif: false,
        });
        setEditing(null);
    }

    function handleSubmit(event: React.FormEvent<HTMLFormElement>) {
        event.preventDefault();

        const url = editing
            ? `/hubin/periode-pkl/${editing.id}`
            : '/hubin/periode-pkl';
        const method = editing ? 'put' : 'post';

        router[method](url, form, {
            preserveScroll: true,
            onSuccess: resetForm,
        });
    }

    function handleEdit(item: any) {
        setEditing(item);
        setForm({
            nama: item.nama ?? '',
            tanggal_mulai: item.tanggal_mulai ?? '',
            tanggal_selesai: item.tanggal_selesai ?? '',
            status_aktif: !!item.status_aktif,
        });
    }

    function handleDelete(item: any) {
        if (!window.confirm('Hapus periode PKL ini?')) {
            return;
        }

        router.delete(`/hubin/periode-pkl/${item.id}`, {
            preserveScroll: true,
        });
    }

    return (
        <>
            <Head title="Master Data Periode PKL" />

            <div className="space-y-6">
                <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold">
                            Master Data Periode PKL
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Kelola jadwal periodik untuk batching PKL.
                        </p>
                    </div>
                </div>

                <SearchFilterBar
                    search={filters.search}
                    route="/hubin/periode-pkl"
                />

                <div className="grid gap-6 lg:grid-cols-[1fr_minmax(320px,420px)]">
                    <div className="overflow-hidden rounded-xl border border-border bg-background shadow-sm">
                        <table className="min-w-full divide-y divide-border text-sm">
                            <thead className="bg-muted/50 text-left text-xs tracking-wider text-muted-foreground uppercase">
                                <tr>
                                    <th className="px-4 py-3">Nama</th>
                                    <th className="px-4 py-3">Mulai</th>
                                    <th className="px-4 py-3">Selesai</th>
                                    <th className="px-4 py-3">Status</th>
                                    <th className="px-4 py-3">Aksi</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-border bg-background">
                                {periodes.data?.map((item: any) => (
                                    <tr key={item.id}>
                                        <td className="px-4 py-3">
                                            {item.nama}
                                        </td>
                                        <td className="px-4 py-3">
                                            {item.tanggal_mulai}
                                        </td>
                                        <td className="px-4 py-3">
                                            {item.tanggal_selesai}
                                        </td>
                                        <td className="px-4 py-3">
                                            {item.status_aktif
                                                ? 'Aktif'
                                                : 'Nonaktif'}
                                        </td>
                                        <td className="space-x-2 px-4 py-3">
                                            <Button
                                                type="button"
                                                variant="secondary"
                                                size="sm"
                                                onClick={() => handleEdit(item)}
                                            >
                                                Edit
                                            </Button>
                                            <Button
                                                type="button"
                                                variant="destructive"
                                                size="sm"
                                                onClick={() =>
                                                    handleDelete(item)
                                                }
                                            >
                                                Hapus
                                            </Button>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                        <div className="border-t border-border px-4 py-3 text-xs text-muted-foreground">
                            Menampilkan {periodes.from} sampai {periodes.to}{' '}
                            dari {periodes.total} periode.
                        </div>
                    </div>

                    <div className="rounded-xl border border-border bg-background p-6 shadow-sm">
                        <h2 className="text-lg font-semibold">
                            {editing
                                ? 'Edit Periode PKL'
                                : 'Tambah Periode PKL'}
                        </h2>
                        <form
                            onSubmit={handleSubmit}
                            className="mt-4 space-y-4"
                        >
                            <div className="space-y-2">
                                <Label htmlFor="nama">Nama</Label>
                                <Input
                                    id="nama"
                                    name="nama"
                                    value={form.nama}
                                    onChange={(e) =>
                                        setForm({
                                            ...form,
                                            nama: e.target.value,
                                        })
                                    }
                                    required
                                />
                            </div>
                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="space-y-2">
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
                                        required
                                    />
                                </div>
                                <div className="space-y-2">
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
                                        required
                                    />
                                </div>
                            </div>
                            <div className="flex items-center gap-2">
                                <input
                                    id="status_aktif"
                                    type="checkbox"
                                    checked={form.status_aktif}
                                    onChange={(e) =>
                                        setForm({
                                            ...form,
                                            status_aktif: e.target.checked,
                                        })
                                    }
                                    className="rounded border-input text-primary shadow-sm focus-visible:ring-ring/50"
                                />
                                <label
                                    htmlFor="status_aktif"
                                    className="text-sm font-medium"
                                >
                                    Status aktif
                                </label>
                            </div>
                            <div className="flex flex-wrap gap-2 pt-2">
                                <Button type="submit">
                                    {editing ? 'Perbarui' : 'Simpan'}
                                </Button>
                                {editing && (
                                    <Button
                                        type="button"
                                        variant="secondary"
                                        onClick={resetForm}
                                    >
                                        Batal
                                    </Button>
                                )}
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </>
    );
}
