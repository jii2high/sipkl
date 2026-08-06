import { Head, router } from '@inertiajs/react';
import { UserCheck } from 'lucide-react';
import { useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
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

            <div className="space-y-6">
                <h2 className="text-2xl font-semibold">
                    Penugasan Guru Pembimbing
                </h2>

                {pengajuans?.length === 0 && (
                    <Card>
                        <CardContent className="py-8 text-center text-muted-foreground">
                            Tidak ada pengajuan yang siap ditugaskan.
                        </CardContent>
                    </Card>
                )}

                <div className="space-y-3">
                    {pengajuans?.map((item: any) => (
                        <Card key={item.id}>
                            <CardHeader>
                                <CardTitle className="flex items-center justify-between text-base">
                                    <span>{item.siswa?.user?.name}</span>
                                    {item.penugasan_aktif ? (
                                        <Badge className="bg-green-100 text-green-800">
                                            Ditugaskan
                                        </Badge>
                                    ) : (
                                        <Badge className="bg-yellow-100 text-yellow-800">
                                            Belum ditugaskan
                                        </Badge>
                                    )}
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-3">
                                <div className="space-y-1 text-sm">
                                    <p>
                                        Perusahaan:{' '}
                                        <span className="font-medium">
                                            {item.perusahaan?.nama}
                                        </span>
                                    </p>
                                    <p>
                                        Kompetensi Keahlian:{' '}
                                        <span className="font-medium">
                                            {item.siswa?.kompetensi_keahlian
                                                ?.nama || '-'}
                                        </span>
                                    </p>
                                    {item.penugasan_aktif && (
                                        <p>
                                            Pembimbing aktif:{' '}
                                            <span className="font-medium">
                                                {item.penugasan_aktif
                                                    .guru_pembimbing?.name ||
                                                    '-'}
                                            </span>
                                        </p>
                                    )}
                                </div>

                                <div className="flex flex-wrap items-end gap-2">
                                    <div className="min-w-52 flex-1">
                                        <Select
                                            value={selected[item.id] || ''}
                                            onValueChange={(v) =>
                                                setSelected((prev) => ({
                                                    ...prev,
                                                    [item.id]: v,
                                                }))
                                            }
                                        >
                                            <SelectTrigger>
                                                <SelectValue placeholder="Pilih guru pembimbing" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {guruPembimbings?.map(
                                                    (gp: any) => (
                                                        <SelectItem
                                                            key={gp.id}
                                                            value={String(
                                                                gp.id,
                                                            )}
                                                        >
                                                            {gp.name}
                                                        </SelectItem>
                                                    ),
                                                )}
                                            </SelectContent>
                                        </Select>
                                    </div>
                                    <Button
                                        size="sm"
                                        onClick={() => assign(item.id)}
                                        disabled={!selected[item.id]}
                                    >
                                        <UserCheck className="mr-1 size-4" />
                                        Tugaskan
                                    </Button>
                                </div>
                            </CardContent>
                        </Card>
                    ))}
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
