import { Head, router } from '@inertiajs/react';
import { RotateCcw, Trash2 } from 'lucide-react';
import type { ReactNode } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { dashboard as hubinDashboard } from '@/routes/hubin';

interface AkunItem {
    id: number;
    name: string;
    email: string;
    role: string;
    nis: string | null;
    detail: string;
}

interface Props {
    akun: AkunItem[];
    perusahaan: { id: number; nama: string; alamat: string }[];
    periode: { id: number; nama: string }[];
    kompetensiKeahlian: { id: number; nama: string }[];
}

function Sektion({
    title,
    items,
    type,
    columns,
    onRestore,
    onForce,
}: {
    title: string;
    items: any[];
    type: string;
    columns: (item: any) => ReactNode;
    onRestore: (type: string, id: number) => void;
    onForce: (type: string, id: number) => void;
}) {
    return (
        <section>
            <h3 className="mb-2 font-semibold">
                {title} ({items.length})
            </h3>
            {items.length === 0 ? (
                <p className="text-sm text-muted-foreground">
                    Recycle bin kosong.
                </p>
            ) : (
                <div className="bg-surface overflow-hidden rounded-lg border">
                    <table className="w-full text-sm">
                        <thead className="text-left text-muted-foreground">
                            <tr className="border-b">
                                <th className="p-3">Nama</th>
                                <th className="p-3">Detail</th>
                                <th className="p-3">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            {items.map((item) => (
                                <tr
                                    key={item.id}
                                    className="border-b last:border-0"
                                >
                                    <td className="p-3 font-medium">
                                        {item.name ?? item.nama}
                                    </td>
                                    <td className="p-3 text-muted-foreground">
                                        {columns(item)}
                                    </td>
                                    <td className="p-3">
                                        <div className="flex gap-2">
                                            <Button
                                                size="sm"
                                                variant="outline"
                                                onClick={() =>
                                                    onRestore(type, item.id)
                                                }
                                            >
                                                <RotateCcw className="mr-1 size-4" />
                                                Pulihkan
                                            </Button>
                                            <Button
                                                size="sm"
                                                variant="ghost"
                                                className="text-destructive"
                                                onClick={() =>
                                                    onForce(type, item.id)
                                                }
                                            >
                                                <Trash2 className="size-4" />
                                            </Button>
                                        </div>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            )}
        </section>
    );
}

export default function RecycleBin({
    akun,
    perusahaan,
    periode,
    kompetensiKeahlian,
}: Props) {
    function restore(type: string, id: number) {
        router.post('/hubin/recycle-bin/restore', { type, id });
    }

    function force(type: string, id: number) {
        if (
            confirm(
                'Hapus permanen? Data tidak dapat dipulihkan lagi (termasuk riwayat terkait).',
            )
        ) {
            router.delete('/hubin/recycle-bin', { data: { type, id } });
        }
    }

    return (
        <>
            <Head title="Recycle Bin" />
            <div className="flex flex-col gap-6 p-4">
                <div>
                    <h2 className="text-2xl font-semibold">Recycle Bin</h2>
                    <p className="text-sm text-muted-foreground">
                        Akun dan master data yang dihapus. Pulihkan atau hapus
                        permanen.
                    </p>
                </div>

                <Sektion
                    title="Akun"
                    type="akun"
                    items={akun}
                    onRestore={restore}
                    onForce={force}
                    columns={(a: AkunItem) => (
                        <span className="flex flex-wrap items-center gap-1">
                            {a.email} · {a.nis ?? '-'}
                            <Badge variant="secondary">{a.role}</Badge>
                        </span>
                    )}
                />

                <Sektion
                    title="Perusahaan"
                    type="perusahaan"
                    items={perusahaan}
                    onRestore={restore}
                    onForce={force}
                    columns={(p) => p.alamat}
                />

                <Sektion
                    title="Periode PKL"
                    type="periode"
                    items={periode}
                    onRestore={restore}
                    onForce={force}
                    columns={() => '-'}
                />

                <Sektion
                    title="Kompetensi Keahlian"
                    type="kompetensiKeahlian"
                    items={kompetensiKeahlian}
                    onRestore={restore}
                    onForce={force}
                    columns={() => '-'}
                />
            </div>
        </>
    );
}

RecycleBin.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: hubinDashboard() },
        { title: 'Recycle Bin' },
    ],
};
