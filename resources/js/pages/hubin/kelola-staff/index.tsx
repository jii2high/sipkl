import { Head, router } from '@inertiajs/react';
import { Download, FileUp } from 'lucide-react';
import { useRef, useState } from 'react';
import SearchFilterBar from '@/components/search-filter-bar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { dashboard as hubinDashboard } from '@/routes/hubin';

interface GuruItem {
    id: number;
    nip: string | null;
    jabatan: string;
    user: { id: number; name: string; email: string };
    kompetensi_keahlian: { id: number; nama: string } | null;
}

interface Props {
    guru: { data: GuruItem[]; links: any[]; last_page: number };
    jurusan: { id: number; nama: string }[];
}

const labelJabatan: Record<string, string> = {
    kaprog: 'Kaprog',
    guru_pembimbing: 'Guru Pembimbing',
    hubin: 'Staf Hubin',
};

export default function KelolaStaff({ guru }: Props) {
    const fileRef = useRef<HTMLInputElement>(null);
    const [importing, setImporting] = useState(false);

    function onImport(e: React.ChangeEvent<HTMLInputElement>) {
        setImporting(false);
        const file = e.target.files?.[0];

        if (!file) {
            return;
        }

        const form = new FormData();
        form.append('file', file);
        router.post('/hubin/kelola-staff/import', form, {
            onSuccess: () => {
                if (fileRef.current) {
                    fileRef.current.value = '';
                }
            },
        });
    }

    function nonaktifkan(g: GuruItem) {
        if (
            confirm(
                `Nonaktifkan ${g.user.name} sebagai Kaprog? Role otomatis menjadi Guru Pembimbing (BR31).`,
            )
        ) {
            router.patch(`/hubin/kelola-staff/${g.id}/nonaktifkan`);
        }
    }

    return (
        <>
            <Head title="Kelola Staff" />
            <div className="flex flex-col gap-4 p-4">
                <div className="flex items-center justify-between">
                    <h2 className="text-2xl font-semibold">Kelola Staff</h2>
                    <div className="flex gap-2">
                        <Button
                            variant="outline"
                            onClick={() => fileRef.current?.click()}
                        >
                            <FileUp className="mr-1 size-4" />
                            Import
                        </Button>
                        <a href="/hubin/kelola-staff/template">
                            <Button variant="outline">
                                <Download className="mr-1 size-4" />
                                Template
                            </Button>
                        </a>
                    </div>
                </div>

                <SearchFilterBar
                    baseRoute="/hubin/kelola-staff"
                    searchPlaceholder="Cari nama staff..."
                    filters={[
                        {
                            key: 'jabatan',
                            label: 'Jabatan',
                            options: Object.entries(labelJabatan).map(
                                ([value, label]) => ({ label, value }),
                            ),
                        },
                    ]}
                />

                <div className="overflow-hidden rounded-lg border bg-surface">
                    <table className="w-full text-sm">
                        <thead className="text-left text-muted-foreground">
                            <tr className="border-b">
                                <th className="p-3">Nama</th>
                                <th className="p-3">NIP</th>
                                <th className="p-3">Jabatan</th>
                                <th className="p-3">Jurusan</th>
                                <th className="p-3">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            {guru.data.map((g) => (
                                <tr key={g.id} className="border-b last:border-0">
                                    <td className="p-3 font-medium">
                                        {g.user.name}
                                    </td>
                                    <td className="p-3">{g.nip ?? '-'}</td>
                                    <td className="p-3">
                                        <Badge variant="secondary">
                                            {labelJabatan[g.jabatan] ?? g.jabatan}
                                        </Badge>
                                    </td>
                                    <td className="p-3 text-muted-foreground">
                                        {g.kompetensi_keahlian?.nama ?? '-'}
                                    </td>
                                    <td className="p-3">
                                        {g.jabatan === 'kaprog' && (
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                className="text-destructive"
                                                onClick={() => nonaktifkan(g)}
                                            >
                                                Nonaktifkan Kaprog
                                            </Button>
                                        )}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>

            {importing && (
                <input
                    ref={fileRef}
                    type="file"
                    accept=".xlsx,.xls"
                    className="hidden"
                    onChange={onImport}
                />
            )}
        </>
    );
}

KelolaStaff.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: hubinDashboard() },
        { title: 'Kelola Staff' },
    ],
};