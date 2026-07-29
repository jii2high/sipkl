import { Head } from '@inertiajs/react';
import { ClipboardList, Clock, GraduationCap } from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

// ponytail: hardcoded sampai pengajuan_pkl migration & model ada
const stats = [
    { title: 'Total Pengajuan PKL', value: 0, icon: ClipboardList },
    { title: 'Siswa Resmi PKL', value: 0, icon: GraduationCap },
    { title: 'Menunggu Verifikasi Kaprog', value: 0, icon: Clock },
];

const stages = [
    { label: 'Draft', count: 0 },
    { label: 'Disetujui Kaprog', count: 0 },
    { label: 'Surat Terbit', count: 0 },
    { label: 'Menunggu Balasan Perusahaan', count: 0 },
    { label: 'Resmi PKL', count: 0 },
];

export default function Dashboard() {
    return (
        <>
            <Head title="Dashboard Hubin" />

            <div className="space-y-6 p-4">
                <div className="space-y-2">
                    <h2 className="text-2xl font-semibold">Dashboard Hubin</h2>
                    <p className="text-sm text-muted-foreground">
                        Selamat datang di SI-PKL.
                    </p>
                </div>

                <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                    {stats.map((s) => (
                        <Card key={s.title}>
                            <CardHeader className="flex flex-row items-center justify-between pb-2">
                                <CardTitle className="text-sm font-medium">
                                    {s.title}
                                </CardTitle>
                                <s.icon className="h-4 w-4 text-muted-foreground" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold">
                                    {s.value}
                                </div>
                            </CardContent>
                        </Card>
                    ))}
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">
                            Pipeline Pengajuan PKL
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="p-0">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="border-b border-border">
                                    <th className="px-6 py-3 text-left font-medium text-muted-foreground">
                                        Status
                                    </th>
                                    <th className="px-6 py-3 text-left font-medium text-muted-foreground">
                                        Jumlah
                                    </th>
                                    <th className="px-6 py-3 text-left font-medium text-muted-foreground">
                                        Persentase
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {stages.map((row) => (
                                    <tr
                                        key={row.label}
                                        className="border-b border-border last:border-0"
                                    >
                                        <td className="px-6 py-4">
                                            {row.label}
                                        </td>
                                        <td className="px-6 py-4">
                                            {row.count}
                                        </td>
                                        <td className="px-6 py-4">0%</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

Dashboard.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: '/hubin/dashboard',
        },
    ],
};
