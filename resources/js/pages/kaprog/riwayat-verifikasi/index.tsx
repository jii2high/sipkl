import { Head, Link, router } from '@inertiajs/react';
import SearchFilterBar from '@/components/search-filter-bar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { dashboard as kaprogDashboard } from '@/routes/kaprog';
import { show as verifikasiShow } from '@/routes/kaprog/verifikasi';

const LABEL_STATUS: Record<string, string> = {
    disetujui: 'Disetujui',
    ditolak: 'Ditolak',
};

export default function RiwayatVerifikasi({ pengajuan }: any) {
    const params = new URLSearchParams(window.location.search);
    const filterStatus = params.get('status') ?? '';

    function setFilter(s: string) {
        const q: Record<string, string> = {};

        if (s) {
            q.status = s;
        }

        const search = params.get('search');

        if (search) {
            q.search = search;
        }

        router.get('/kaprog/riwayat-verifikasi', q, { preserveState: true });
    }

    return (
        <>
            <Head title="Riwayat Verifikasi" />

            <div className="flex flex-col gap-4 p-4">
                <h2 className="text-2xl font-semibold">Riwayat Verifikasi</h2>

                <div className="flex flex-wrap items-center gap-2">
                    <SearchFilterBar
                        baseRoute="/kaprog/riwayat-verifikasi"
                        searchPlaceholder="Cari nama siswa..."
                    />
                    {/* Filter button status */}
                    <div className="flex gap-1">
                        {(['', 'disetujui', 'ditolak'] as const).map((s) => (
                            <button
                                key={s || 'semua'}
                                onClick={() => setFilter(s)}
                                className={`rounded-full px-3 py-1 text-sm transition-colors ${
                                    filterStatus === s
                                        ? 'bg-primary text-primary-foreground'
                                        : 'border bg-transparent hover:bg-muted'
                                }`}
                            >
                                {s === '' ? 'Semua' : LABEL_STATUS[s]}
                            </button>
                        ))}
                    </div>
                </div>

                <div className="overflow-hidden rounded-lg border">
                    <table className="w-full text-sm">
                        <thead className="text-left text-muted-foreground">
                            <tr className="border-b">
                                <th className="p-3">No</th>
                                <th className="p-3">Nama Siswa</th>
                                <th className="p-3">Perusahaan</th>
                                <th className="p-3">Status</th>
                                <th className="p-3">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            {pengajuan?.data?.length === 0 && (
                                <tr>
                                    <td colSpan={5} className="p-6 text-center text-muted-foreground">
                                        Belum ada pengajuan yang diverifikasi.
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
                                    <td className="p-3">
                                        <Badge
                                            variant={
                                                item.status === 'disetujui' ? 'default' : 'destructive'
                                            }
                                        >
                                            {LABEL_STATUS[item.status] ?? item.status}
                                        </Badge>
                                    </td>
                                    <td className="p-3">
                                        <Link href={verifikasiShow({ pengajuan: item.id }).url}>
                                            <Button variant="ghost" size="sm">
                                                Detail
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
                            .map((l: any) => (
                                <Link
                                    key={l.label}
                                    href={l.url || '#'}
                                    className={`rounded px-3 py-1 text-sm ${l.active ? 'bg-primary text-primary-foreground' : 'bg-muted'}`}
                                    preserveState
                                >
                                    {l.label}
                                </Link>
                            ))}
                    </div>
                )}
            </div>
        </>
    );
}

RiwayatVerifikasi.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: kaprogDashboard() },
        { title: 'Riwayat Verifikasi' },
    ],
};
