import { Head, Link } from '@inertiajs/react';
import SearchFilterBar from '@/components/search-filter-bar';
import { Button } from '@/components/ui/button';
import { dashboard as kaprogDashboard } from '@/routes/kaprog';
import {
    index as verifikasiIndex,
    show as verifikasiShow,
} from '@/routes/kaprog/verifikasi';

export default function Verifikasi({ pengajuan }: any) {
    return (
        <>
            <Head title="Verifikasi Pengajuan" />

            <div className="flex flex-col gap-4 p-4">
                <h2 className="text-2xl font-semibold">Antrian Verifikasi PKL</h2>

                <SearchFilterBar
                    baseRoute="/kaprog/verifikasi"
                    searchPlaceholder="Cari nama siswa..."
                />

                <div className="overflow-hidden rounded-lg border">
                    <table className="w-full text-sm">
                        <thead className="text-left text-muted-foreground">
                            <tr className="border-b">
                                <th className="p-3">No</th>
                                <th className="p-3">Nama Siswa</th>
                                <th className="p-3">Perusahaan</th>
                                <th className="p-3">Periode</th>
                                <th className="p-3">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            {pengajuan?.data?.length === 0 && (
                                <tr>
                                    <td colSpan={5} className="p-6 text-center text-muted-foreground">
                                        Tidak ada pengajuan yang menunggu verifikasi.
                                    </td>
                                </tr>
                            )}
                            {pengajuan?.data?.map((item: any, idx: number) => (
                                <tr key={item.id} className="border-b last:border-0">
                                    <td className="p-3 text-muted-foreground">
                                        {(pengajuan.current_page - 1) * pengajuan.per_page + idx + 1}
                                    </td>
                                    <td className="p-3 font-medium">
                                        {item.siswa?.user?.name ?? '-'}
                                    </td>
                                    <td className="p-3">{item.perusahaan?.nama ?? '-'}</td>
                                    <td className="p-3 text-muted-foreground">
                                        {item.periode?.nama ?? '-'}
                                    </td>
                                    <td className="p-3">
                                        <Link href={verifikasiShow({ pengajuan: item.id }).url}>
                                            <Button variant="outline" size="sm">
                                                Lihat Detail
                                            </Button>
                                        </Link>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>

                {pengajuan?.last_page > 1 && (
                    <div className="flex justify-center gap-2">
                        {pengajuan.links
                            ?.filter((l: any) => !l.label.includes('...'))
                            .map((link: any) => (
                                <Link
                                    key={link.label}
                                    href={link.url || '#'}
                                    className={`rounded px-3 py-1 text-sm ${link.active ? 'bg-primary text-primary-foreground' : 'bg-muted'}`}
                                    preserveState
                                >
                                    {link.label}
                                </Link>
                            ))}
                    </div>
                )}
            </div>
        </>
    );
}

Verifikasi.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: kaprogDashboard() },
        { title: 'Verifikasi Pengajuan', href: verifikasiIndex() },
    ],
};
