import { Head, router } from '@inertiajs/react';
import { Download, FileUp, Trash2 } from 'lucide-react';
import { useRef, useState } from 'react';
import SearchFilterBar from '@/components/search-filter-bar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { dashboard as hubinDashboard } from '@/routes/hubin';

interface SiswaItem {
    id: number;
    nis: string;
    alamat: string | null;
    no_telp: string | null;
    user: { id: number; name: string; email: string };
    kompetensi_keahlian: { id: number; nama: string; acronym: string } | null;
    periode: { id: number; nama: string } | null;
}

interface Props {
    siswa: { data: SiswaItem[]; links: any[]; last_page: number };
    jurusan: { id: number; nama: string }[];
}

export default function KelolaSiswa({ siswa, jurusan }: Props) {
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
        router.post('/hubin/kelola-siswa/import', form, {
            onSuccess: () => {
                if (fileRef.current) {
                    fileRef.current.value = '';
                }
            },
        });
    }

    function hapus(id: number) {
        if (confirm('Hapus siswa ini? (masuk recycle bin)')) {
            router.delete(`/hubin/kelola-siswa/${id}`);
        }
    }

    return (
        <>
            <Head title="Kelola Siswa" />
            <div className="flex flex-col gap-4 p-4">
                <div className="flex items-center justify-between">
                    <h2 className="text-2xl font-semibold">Kelola Siswa</h2>
                    <div className="flex gap-2">
                        <Button
                            variant="outline"
                            onClick={() => fileRef.current?.click()}
                        >
                            <FileUp className="mr-1 size-4" />
                            Import
                        </Button>
                        <a href="/hubin/kelola-siswa/export">
                            <Button variant="outline">
                                <Download className="mr-1 size-4" />
                                Export
                            </Button>
                        </a>
                    </div>
                </div>

                <SearchFilterBar
                    baseRoute="/hubin/kelola-siswa"
                    searchPlaceholder="Cari nama siswa..."
                    filters={[
                        {
                            key: 'kompetensi_keahlian_id',
                            label: 'Jurusan',
                            options: jurusan.map((j) => ({
                                label: j.nama,
                                value: String(j.id),
                            })),
                        },
                    ]}
                />

                <div className="overflow-hidden rounded-lg border bg-surface">
                    <table className="w-full text-sm">
                        <thead className="text-left text-muted-foreground">
                            <tr className="border-b">
                                <th className="p-3">Nama</th>
                                <th className="p-3">NIS</th>
                                <th className="p-3">Jurusan</th>
                                <th className="p-3">Periode</th>
                                <th className="p-3">Email</th>
                                <th className="p-3">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            {siswa.data.map((s) => (
                                <tr key={s.id} className="border-b last:border-0">
                                    <td className="p-3 font-medium">
                                        {s.user.name}
                                    </td>
                                    <td className="p-3">{s.nis}</td>
                                    <td className="p-3">
                                        <Badge variant="secondary">
                                            {s.kompetensi_keahlian?.acronym ??
                                                '-'}
                                        </Badge>
                                    </td>
                                    <td className="p-3 text-muted-foreground">
                                        {s.periode?.nama ?? '-'}
                                    </td>
                                    <td className="p-3 text-muted-foreground">
                                        {s.user.email}
                                    </td>
                                    <td className="p-3">
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            className="text-destructive"
                                            onClick={() => hapus(s.user.id)}
                                        >
                                            <Trash2 className="size-4" />
                                        </Button>
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

KelolaSiswa.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: hubinDashboard() },
        { title: 'Kelola Siswa' },
    ],
};