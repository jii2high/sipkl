import { Head, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import SearchFilterBar from '@/components/search-filter-bar';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

export default function SectionPenilaianIndex() {
    const { sectionPenilaians, indikatorOptions, filters } = usePage()
        .props as any;
    const [form, setForm] = useState({
        indikator_penilaian_id: '',
        nomor_urut: '1',
        deskripsi: '',
    });
    const [editing, setEditing] = useState<any>(null);

    function resetForm() {
        setForm({ indikator_penilaian_id: '', nomor_urut: '1', deskripsi: '' });
        setEditing(null);
    }

    function handleSubmit(event: React.FormEvent<HTMLFormElement>) {
        event.preventDefault();

        const url = editing
            ? `/hubin/section-penilaian/${editing.id}`
            : '/hubin/section-penilaian';
        const method = editing ? 'put' : 'post';

        router[method](
            url,
            {
                ...form,
                nomor_urut: Number(form.nomor_urut),
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
            indikator_penilaian_id: item.indikator_penilaian_id
                ? String(item.indikator_penilaian_id)
                : '',
            nomor_urut: String(item.nomor_urut ?? 1),
            deskripsi: item.deskripsi ?? '',
        });
    }

    function handleDelete(item: any) {
        if (!window.confirm('Hapus section penilaian ini?')) {
            return;
        }

        router.delete(`/hubin/section-penilaian/${item.id}`, {
            preserveScroll: true,
        });
    }

    return (
        <>
            <Head title="Master Data Section Penilaian" />

            <div className="space-y-6">
                <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold">
                            Master Data Section Penilaian
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Kelola section penilaian untuk indikator yang
                            memiliki sub-komponen.
                        </p>
                    </div>
                </div>

                <SearchFilterBar
                    search={filters.search}
                    route="/hubin/section-penilaian"
                />

                <div className="grid gap-6 lg:grid-cols-[1fr_minmax(360px,440px)]">
                    <div className="overflow-hidden rounded-xl border border-border bg-background shadow-sm">
                        <table className="min-w-full divide-y divide-border text-sm">
                            <thead className="bg-muted/50 text-left text-xs tracking-wider text-muted-foreground uppercase">
                                <tr>
                                    <th className="px-4 py-3">Indikator</th>
                                    <th className="px-4 py-3">Deskripsi</th>
                                    <th className="px-4 py-3">Aksi</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-border bg-background">
                                {sectionPenilaians.data?.map((item: any) => (
                                    <tr key={item.id}>
                                        <td className="px-4 py-3">
                                            {item.indikator_penilaian
                                                ?.deskripsi ?? '-'}
                                        </td>
                                        <td className="px-4 py-3">
                                            {item.deskripsi}
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
                            Menampilkan {sectionPenilaians.from} sampai{' '}
                            {sectionPenilaians.to} dari{' '}
                            {sectionPenilaians.total} section.
                        </div>
                    </div>

                    <div className="rounded-xl border border-border bg-background p-6 shadow-sm">
                        <h2 className="text-lg font-semibold">
                            {editing
                                ? 'Edit Section Penilaian'
                                : 'Tambah Section Penilaian'}
                        </h2>
                        <form
                            onSubmit={handleSubmit}
                            className="mt-4 space-y-4"
                        >
                            <div className="space-y-2">
                                <Label htmlFor="indikator_penilaian_id">
                                    Indikator Penilaian
                                </Label>
                                <select
                                    id="indikator_penilaian_id"
                                    value={form.indikator_penilaian_id}
                                    onChange={(e) =>
                                        setForm({
                                            ...form,
                                            indikator_penilaian_id:
                                                e.target.value,
                                        })
                                    }
                                    className="block w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm outline-none focus-visible:ring-ring/50"
                                    required
                                >
                                    <option value="">Pilih indikator</option>
                                    {indikatorOptions.map((item: any) => (
                                        <option
                                            key={item.value}
                                            value={item.value}
                                        >
                                            {item.label}
                                        </option>
                                    ))}
                                </select>
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="nomor_urut">Nomor Urut</Label>
                                <Input
                                    id="nomor_urut"
                                    type="number"
                                    min="1"
                                    value={form.nomor_urut}
                                    onChange={(e) =>
                                        setForm({
                                            ...form,
                                            nomor_urut: e.target.value,
                                        })
                                    }
                                    required
                                />
                            </div>
                            <div className="space-y-2">
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
