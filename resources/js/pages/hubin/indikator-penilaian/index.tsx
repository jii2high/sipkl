import { Head, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import SearchFilterBar from '@/components/search-filter-bar';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

export default function IndikatorPenilaianIndex() {
    const { indikatorPenilaians, kompetensiKeahlians, filters } = usePage()
        .props as any;
    const [form, setForm] = useState({
        kategori: 'umum',
        kompetensi_keahlian_id: '',
        nomor_urut: '1',
        deskripsi: '',
    });
    const [editing, setEditing] = useState<any>(null);

    function resetForm() {
        setForm({
            kategori: 'umum',
            kompetensi_keahlian_id: '',
            nomor_urut: '1',
            deskripsi: '',
        });
        setEditing(null);
    }

    function handleSubmit(event: React.FormEvent<HTMLFormElement>) {
        event.preventDefault();

        const url = editing
            ? `/hubin/indikator-penilaian/${editing.id}`
            : '/hubin/indikator-penilaian';
        const method = editing ? 'put' : 'post';

        router[method](
            url,
            {
                ...form,
                kompetensi_keahlian_id: form.kompetensi_keahlian_id || null,
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
            kategori: item.kategori ?? 'umum',
            kompetensi_keahlian_id: item.kompetensi_keahlian_id
                ? String(item.kompetensi_keahlian_id)
                : '',
            nomor_urut: String(item.nomor_urut ?? 1),
            deskripsi: item.deskripsi ?? '',
        });
    }

    function handleDelete(item: any) {
        if (!window.confirm('Hapus indikator penilaian ini?')) {
            return;
        }

        router.delete(`/hubin/indikator-penilaian/${item.id}`, {
            preserveScroll: true,
        });
    }

    return (
        <>
            <Head title="Master Data Indikator Penilaian" />

            <div className="space-y-6">
                <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold">
                            Master Data Indikator Penilaian
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Kelola indikator penilaian umum dan teknis kejuruan.
                        </p>
                    </div>
                </div>

                <SearchFilterBar
                    search={filters.search}
                    route="/hubin/indikator-penilaian"
                />

                <div className="grid gap-6 lg:grid-cols-[1fr_minmax(360px,440px)]">
                    <div className="overflow-hidden rounded-xl border border-border bg-background shadow-sm">
                        <table className="min-w-full divide-y divide-border text-sm">
                            <thead className="bg-muted/50 text-left text-xs tracking-wider text-muted-foreground uppercase">
                                <tr>
                                    <th className="px-4 py-3">Kategori</th>
                                    <th className="px-4 py-3">Deskripsi</th>
                                    <th className="px-4 py-3">Kompetensi</th>
                                    <th className="px-4 py-3">Aksi</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-border bg-background">
                                {indikatorPenilaians.data?.map((item: any) => (
                                    <tr key={item.id}>
                                        <td className="px-4 py-3">
                                            {item.kategori === 'umum'
                                                ? 'Umum'
                                                : 'Teknis Kejuruan'}
                                        </td>
                                        <td className="px-4 py-3">
                                            {item.deskripsi}
                                        </td>
                                        <td className="px-4 py-3">
                                            {item.kompetensi_keahlian?.nama ??
                                                '-'}
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
                            Menampilkan {indikatorPenilaians.from} sampai{' '}
                            {indikatorPenilaians.to} dari{' '}
                            {indikatorPenilaians.total} indikator.
                        </div>
                    </div>

                    <div className="rounded-xl border border-border bg-background p-6 shadow-sm">
                        <h2 className="text-lg font-semibold">
                            {editing
                                ? 'Edit Indikator Penilaian'
                                : 'Tambah Indikator Penilaian'}
                        </h2>
                        <form
                            onSubmit={handleSubmit}
                            className="mt-4 space-y-4"
                        >
                            <div className="space-y-2">
                                <Label htmlFor="kategori">Kategori</Label>
                                <select
                                    id="kategori"
                                    value={form.kategori}
                                    onChange={(e) =>
                                        setForm({
                                            ...form,
                                            kategori: e.target.value,
                                        })
                                    }
                                    className="block w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm outline-none focus-visible:ring-ring/50"
                                >
                                    <option value="umum">Umum</option>
                                    <option value="teknis_kejuruan">
                                        Teknis Kejuruan
                                    </option>
                                </select>
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="kompetensi_keahlian_id">
                                    Kompetensi Keahlian
                                </Label>
                                <select
                                    id="kompetensi_keahlian_id"
                                    value={form.kompetensi_keahlian_id}
                                    onChange={(e) =>
                                        setForm({
                                            ...form,
                                            kompetensi_keahlian_id:
                                                e.target.value,
                                        })
                                    }
                                    className="block w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm outline-none focus-visible:ring-ring/50"
                                >
                                    <option value="">
                                        Pilih jika teknis kejuruan
                                    </option>
                                    {kompetensiKeahlians.map((item: any) => (
                                        <option key={item.id} value={item.id}>
                                            {item.nama}
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
