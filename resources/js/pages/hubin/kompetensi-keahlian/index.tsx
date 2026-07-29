import { Head, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import SearchFilterBar from '@/components/search-filter-bar';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

export default function KompetensiKeahlianIndex() {
    const { kompetensiKeahlians, filters } = usePage().props as any;
    const [form, setForm] = useState({ nama: '', acronym: '' });
    const [editing, setEditing] = useState<any>(null);

    function resetForm() {
        setForm({ nama: '', acronym: '' });
        setEditing(null);
    }

    function handleSubmit(event: React.FormEvent<HTMLFormElement>) {
        event.preventDefault();

        const url = editing
            ? `/hubin/kompetensi-keahlian/${editing.id}`
            : '/hubin/kompetensi-keahlian';
        const method = editing ? 'put' : 'post';

        router[method](url, form, {
            preserveScroll: true,
            onSuccess: resetForm,
        });
    }

    function handleEdit(item: any) {
        setEditing(item);
        setForm({ nama: item.nama ?? '', acronym: item.acronym ?? '' });
    }

    function handleDelete(item: any) {
        if (!window.confirm('Hapus kompetensi keahlian ini?')) {
            return;
        }

        router.delete(`/hubin/kompetensi-keahlian/${item.id}`, {
            preserveScroll: true,
        });
    }

    return (
        <>
            <Head title="Master Data Kompetensi Keahlian" />

            <div className="space-y-6">
                <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold">
                            Master Data Kompetensi Keahlian
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Kelola daftar kompetensi keahlian.
                        </p>
                    </div>
                </div>

                <SearchFilterBar
                    search={filters.search}
                    route="/hubin/kompetensi-keahlian"
                />

                <div className="grid gap-6 lg:grid-cols-[1fr_minmax(320px,420px)]">
                    <div className="overflow-hidden rounded-xl border border-border bg-background shadow-sm">
                        <table className="min-w-full divide-y divide-border text-sm">
                            <thead className="bg-muted/50 text-left text-xs tracking-wider text-muted-foreground uppercase">
                                <tr>
                                    <th className="px-4 py-3">Nama</th>
                                    <th className="px-4 py-3">Acronym</th>
                                    <th className="px-4 py-3">Aksi</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-border bg-background">
                                {kompetensiKeahlians.data?.map((item: any) => (
                                    <tr key={item.id}>
                                        <td className="px-4 py-3">
                                            {item.nama}
                                        </td>
                                        <td className="px-4 py-3">
                                            {item.acronym}
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
                            Menampilkan {kompetensiKeahlians.from} sampai{' '}
                            {kompetensiKeahlians.to} dari{' '}
                            {kompetensiKeahlians.total} item.
                        </div>
                    </div>

                    <div className="rounded-xl border border-border bg-background p-6 shadow-sm">
                        <h2 className="text-lg font-semibold">
                            {editing
                                ? 'Edit Kompetensi Keahlian'
                                : 'Tambah Kompetensi Keahlian'}
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
                                <Label htmlFor="acronym">Acronym</Label>
                                <Input
                                    id="acronym"
                                    name="acronym"
                                    value={form.acronym}
                                    onChange={(e) =>
                                        setForm({
                                            ...form,
                                            acronym: e.target.value,
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
