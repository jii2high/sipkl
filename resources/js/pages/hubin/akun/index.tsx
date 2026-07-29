import { Head, router, usePage } from '@inertiajs/react';
import SearchFilterBar from '@/components/search-filter-bar';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';

export default function AkunIndex() {
    const { guruProfiles, filters, roles } = usePage().props as any;

    function handleDeactivate(item: any) {
        if (!window.confirm('Nonaktifkan kaprog ini?')) {
            return;
        }

        router.post(
            `/hubin/akun/${item.id}/nonaktifkan-kaprog`,
            {},
            {
                preserveScroll: true,
            },
        );
    }

    return (
        <>
            <Head title="Kelola Akun" />

            <div className="space-y-6">
                <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold">Kelola Akun</h1>
                        <p className="text-sm text-muted-foreground">
                            Lihat akun guru dan kaprog serta nonaktifkan kaprog
                            jika dibutuhkan.
                        </p>
                    </div>
                </div>

                <SearchFilterBar
                    search={filters.search}
                    filters={[
                        {
                            name: 'role',
                            value: filters.role ?? null,
                            options: roles,
                        },
                    ]}
                    route="/hubin/akun"
                />

                <div className="overflow-hidden rounded-xl border border-border bg-background shadow-sm">
                    <table className="min-w-full divide-y divide-border text-sm">
                        <thead className="bg-muted/50 text-left text-xs tracking-wider text-muted-foreground uppercase">
                            <tr>
                                <th className="px-4 py-3">Nama</th>
                                <th className="px-4 py-3">NIP</th>
                                <th className="px-4 py-3">Jabatan</th>
                                <th className="px-4 py-3">Kompetensi</th>
                                <th className="px-4 py-3">Aksi</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-border bg-background">
                            {guruProfiles.data?.map((item: any) => (
                                <tr key={item.id}>
                                    <td className="px-4 py-3">
                                        {item.user?.name}
                                    </td>
                                    <td className="px-4 py-3">{item.nip}</td>
                                    <td className="px-4 py-3">
                                        {item.jabatan}
                                    </td>
                                    <td className="px-4 py-3">
                                        {item.kompetensi_keahlian?.nama ?? '-'}
                                    </td>
                                    <td className="space-x-2 px-4 py-3">
                                        {item.jabatan === 'kaprog' ? (
                                            <Button
                                                type="button"
                                                variant="destructive"
                                                size="sm"
                                                onClick={() =>
                                                    handleDeactivate(item)
                                                }
                                            >
                                                Nonaktifkan Kaprog
                                            </Button>
                                        ) : (
                                            <span className="text-xs text-muted-foreground">
                                                -
                                            </span>
                                        )}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                    <div className="border-t border-border px-4 py-3 text-xs text-muted-foreground">
                        Menampilkan {guruProfiles.from} sampai {guruProfiles.to}{' '}
                        dari {guruProfiles.total} akun.
                    </div>
                </div>
            </div>
        </>
    );
}
