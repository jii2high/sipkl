import { Head, router } from '@inertiajs/react';
import { Download, Upload, FileDown } from 'lucide-react';
import { useRef } from 'react';
import SearchFilterBar from '@/components/search-filter-bar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { dashboard as hubinDashboard } from '@/routes/hubin';

interface AkunItem {
    id: number;
    user: { id: number; name: string; email: string };
    nip: string | null;
    jabatan: string;
    kompetensi_keahlian: { id: number; nama: string } | null;
}

interface Props {
    akun: { data: AkunItem[]; meta: any };
}

export default function Index({ akun }: Props) {
    const guruInput = useRef<HTMLInputElement>(null);
    const siswaInput = useRef<HTMLInputElement>(null);

    function upload(type: 'guru' | 'siswa', file: File | null) {
        if (!file) {
            return;
        }

        const form = new FormData();
        form.append('file', file);
        router.post(`/hubin/kelola-akun/import-${type}`, form);
    }

    return (
        <>
            <Head title="Kelola Akun Kaprog" />
            <div className="flex flex-col gap-4 p-4">
                <div className="flex items-center justify-between">
                    <h2 className="text-2xl font-semibold">
                        Kelola Akun Kaprog
                    </h2>
                    <div className="flex gap-2">
                        <input
                            ref={guruInput}
                            type="file"
                            accept=".xlsx,.xls"
                            className="hidden"
                            onChange={(e) =>
                                upload('guru', e.target.files?.[0] ?? null)
                            }
                        />
                        <input
                            ref={siswaInput}
                            type="file"
                            accept=".xlsx,.xls"
                            className="hidden"
                            onChange={(e) =>
                                upload('siswa', e.target.files?.[0] ?? null)
                            }
                        />
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={() => guruInput.current?.click()}
                        >
                            <Upload className="mr-1 h-4 w-4" /> Import Guru
                        </Button>
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={() => siswaInput.current?.click()}
                        >
                            <Upload className="mr-1 h-4 w-4" /> Import Siswa
                        </Button>
                        <Button variant="outline" size="sm" asChild>
                            <a href="/hubin/kelola-akun/export">
                                <Download className="mr-1 h-4 w-4" /> Export
                            </a>
                        </Button>
                        <Button variant="ghost" size="sm" asChild>
                            <a href="/hubin/kelola-akun/template-guru">
                                <FileDown className="mr-1 h-4 w-4" /> Template
                                Guru
                            </a>
                        </Button>
                        <Button variant="ghost" size="sm" asChild>
                            <a href="/hubin/kelola-akun/template-siswa">
                                <FileDown className="mr-1 h-4 w-4" /> Template
                                Siswa
                            </a>
                        </Button>
                    </div>
                </div>
                <SearchFilterBar
                    baseRoute="/hubin/kelola-akun"
                    searchPlaceholder="Cari nama..."
                />

                <div className="bg-surface rounded-lg border">
                    <table className="w-full text-sm">
                        <thead>
                            <tr className="border-b text-left text-muted-foreground">
                                <th className="p-3">Nama</th>
                                <th className="p-3">NIP</th>
                                <th className="p-3">Email</th>
                                <th className="p-3">Jurusan</th>
                                <th className="p-3">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            {akun.data.map((a) => (
                                <tr
                                    key={a.id}
                                    className="border-b last:border-0"
                                >
                                    <td className="p-3 font-medium">
                                        {a.user.name}
                                    </td>
                                    <td className="p-3 text-muted-foreground">
                                        {a.nip}
                                    </td>
                                    <td className="p-3 text-muted-foreground">
                                        {a.user.email}
                                    </td>
                                    <td className="p-3">
                                        <Badge variant="outline">
                                            {a.kompetensi_keahlian?.nama ?? '-'}
                                        </Badge>
                                    </td>
                                    <td className="p-3">
                                        <Button
                                            variant="destructive"
                                            size="sm"
                                            onClick={() => {
                                                if (
                                                    !confirm(
                                                        'Yakin ingin menonaktifkan akun Kaprog ini? Jabatan akan berubah menjadi Guru Pembimbing.',
                                                    )
                                                ) {
                                                    return;
                                                }

                                                router.patch(
                                                    `/hubin/kelola-akun/${a.id}/nonaktifkan`,
                                                );
                                            }}
                                        >
                                            Nonaktifkan sebagai Kaprog
                                        </Button>
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

Index.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: hubinDashboard() },
        { title: 'Kelola Akun' },
    ],
};
