import { Head, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import SearchFilterBar from '@/components/search-filter-bar';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

export default function PerusahaanIndex() {
    const { perusahaans, filters } = usePage().props as any;
    const [form, setForm] = useState({
        nama: '',
        alamat: '',
        telp: '',
        kuota_saran: '6',
    });
    const [editing, setEditing] = useState<any>(null);

    function resetForm() {
        setForm({ nama: '', alamat: '', telp: '', kuota_saran: '6' });
        setEditing(null);
    }

    function handleSubmit(event: React.FormEvent<HTMLFormElement>) {
        event.preventDefault();

        const url = editing
            ? `/hubin/perusahaan/${editing.id}`
            : '/hubin/perusahaan';
        const method = editing ? 'put' : 'post';

        router[method](
            url,
            {
                ...form,
                kuota_saran: Number(form.kuota_saran),
            },
            {
                preserveScroll: true,
                onSuccess: resetForm,
            },
        );
    }

    function handleEdit(item: any) {
        setEditing(item);
        setForm({
            nama: item.nama ?? '',
            alamat: item.alamat ?? '',
            telp: item.telp ?? '',
            kuota_saran: String(item.kuota_saran ?? 6),
        });
    }

    function handleDelete(item: any) {
        if (!window.confirm('Hapus perusahaan ini?')) {
            return;
        }

        router.delete(`/hubin/perusahaan/${item.id}`, {
            preserveScroll: true,
        });
    }

    return (
        <>
            <Head title="Master Data Perusahaan" />

            <div className="space-y-6">
                <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold">
                            Master Data Perusahaan
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Kelola data perusahaan mitra PKL.
                        </p>
                    </div>
                </div>

                <SearchFilterBar
                    search={filters.search}
                    route="/hubin/perusahaan"
                />

                <div className="grid gap-6 lg:grid-cols-[1fr_minmax(320px,420px)]">
                    <div className="overflow-hidden rounded-xl border border-border bg-background shadow-sm">
                        <table className="min-w-full divide-y divide-border text-sm">
                            <thead className="bg-muted/50 text-left text-xs tracking-wider text-muted-foreground uppercase">
                                <tr>
                                    <th className="px-4 py-3">Nama</th>
                                    <th className="px-4 py-3">Telp</th>
                                    <th className="px-4 py-3">Kuota</th>
                                    <th className="px-4 py-3">Aksi</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-border bg-background">
                                {perusahaans.data?.map((item: any) => (
                                    <tr key={item.id}>
                                        <td className="px-4 py-3">
                                            {item.nama}
                                        </td>
                                        <td className="px-4 py-3">
                                            {item.telp || '-'}
                                        </td>
                                        <td className="px-4 py-3">
                                            {item.kuota_saran}
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
                            Menampilkan {perusahaans.from} sampai{' '}
                            {perusahaans.to} dari {perusahaans.total}{' '}
                            perusahaan.
                        </div>
                    </div>

                    <div className="rounded-xl border border-border bg-background p-6 shadow-sm">
                        <h2 className="text-lg font-semibold">
                            {editing ? 'Edit Perusahaan' : 'Tambah Perusahaan'}
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
                            <div className="space-y-2">
                                <Label htmlFor="alamat">Alamat</Label>
                                <Input
                                    id="alamat"
                                    name="alamat"
                                    value={form.alamat}
                                    onChange={(e) =>
                                        setForm({
                                            ...form,
                                            alamat: e.target.value,
                                        })
                                    }
                                />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="telp">Telepon</Label>
                                <Input
                                    id="telp"
                                    name="telp"
                                    value={form.telp}
                                    onChange={(e) =>
                                        setForm({
                                            ...form,
                                            telp: e.target.value,
                                        })
                                    }
                                />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="kuota_saran">Kuota Saran</Label>
                                <Input
                                    id="kuota_saran"
                                    name="kuota_saran"
                                    type="number"
                                    min="0"
                                    max="255"
                                    value={form.kuota_saran}
                                    onChange={(e) =>
                                        setForm({
                                            ...form,
                                            kuota_saran: e.target.value,
                                        })
                                    }
                                    required
                                />
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
