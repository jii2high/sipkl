import { Head, router } from '@inertiajs/react';
import { UserCheck } from 'lucide-react';
import { useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { dashboard as kaprogDashboard } from '@/routes/kaprog';
import {
    index as penugasanIndex,
    store as penugasanStore,
} from '@/routes/kaprog/penugasan';

export default function Penugasan({ pengajuans, guruPembimbings }: any) {
    const [selected, setSelected] = useState<Record<number, string>>({});

    function assign(pengajuanId: number) {
        const guruPembimbingId = selected[pengajuanId];

        if (!guruPembimbingId) {
            return;
        }

        if (confirm('Yakin ingin menugaskan guru pembimbing ini?')) {
            router.post(
                penugasanStore({ pengajuan: pengajuanId }).url,
                { guru_pembimbing_id: guruPembimbingId },
                { preserveScroll: true },
            );
        }
    }

    return (
        <>
            <Head title="Penugasan Pembimbing" />

            <div className="flex flex-col gap-4 p-4">
                <h2 className="text-2xl font-semibold">Penugasan Guru Pembimbing</h2>

                <div className="overflow-hidden rounded-lg border">
                    <table className="w-full text-sm">
                        <thead className="text-left text-muted-foreground">
                            <tr className="border-b">
                                <th className="p-3">No</th>
                                <th className="p-3">Nama Siswa</th>
                                <th className="p-3">Perusahaan</th>
                                <th className="p-3">Jurusan</th>
                                <th className="p-3">Pembimbing Aktif</th>
                                <th className="p-3 min-w-64">Tugaskan</th>
                            </tr>
                        </thead>
                        <tbody>
                            {pengajuans?.length === 0 && (
                                <tr>
                                    <td colSpan={6} className="p-6 text-center text-muted-foreground">
                                        Tidak ada pengajuan yang siap ditugaskan.
                                    </td>
                                </tr>
                            )}
                            {pengajuans?.map((item: any, idx: number) => (
                                <tr key={item.id} className="border-b last:border-0">
                                    <td className="p-3 text-muted-foreground">{idx + 1}</td>
                                    <td className="p-3 font-medium">
                                        {item.siswa?.user?.name ?? '-'}
                                    </td>
                                    <td className="p-3">{item.perusahaan?.nama ?? '-'}</td>
                                    <td className="p-3 text-muted-foreground">
                                        {item.siswa?.kompetensi_keahlian?.nama ?? '-'}
                                    </td>
                                    <td className="p-3">
                                        {item.penugasan_aktif ? (
                                            <Badge variant="default">
                                                {item.penugasan_aktif.guru_pembimbing?.name ?? '-'}
                                            </Badge>
                                        ) : (
                                            <Badge variant="secondary">Belum ditugaskan</Badge>
                                        )}
                                    </td>
                                    <td className="p-3">
                                        <div className="flex items-center gap-2">
                                            <Select
                                                value={selected[item.id] || ''}
                                                onValueChange={(v) =>
                                                    setSelected((prev) => ({
                                                        ...prev,
                                                        [item.id]: v,
                                                    }))
                                                }
                                            >
                                                <SelectTrigger className="w-44">
                                                    <SelectValue placeholder="Pilih guru..." />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    {guruPembimbings?.map((gp: any) => (
                                                        <SelectItem key={gp.id} value={String(gp.id)}>
                                                            {gp.name}
                                                        </SelectItem>
                                                    ))}
                                                </SelectContent>
                                            </Select>
                                            <Button
                                                size="sm"
                                                onClick={() => assign(item.id)}
                                                disabled={!selected[item.id]}
                                            >
                                                <UserCheck className="mr-1 size-4" />
                                                Tugaskan
                                            </Button>
                                        </div>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>
        </>
    );
}

Penugasan.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: kaprogDashboard() },
        { title: 'Penugasan Pembimbing', href: penugasanIndex() },
    ],
};
