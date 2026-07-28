import { Head } from '@inertiajs/react';
import { dashboard as siswaDashboard } from '@/routes/siswa';

export default function Dashboard() {
    return (
        <>
            <Head title="Dashboard Siswa" />
            <div className="flex h-full flex-1 flex-col items-center justify-center gap-4 p-4">
                <h2 className="text-2xl font-semibold">Dashboard Siswa</h2>
                <p className="text-muted-foreground">
                    Selamat datang di SI-PKL
                </p>
            </div>
        </>
    );
}

Dashboard.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: siswaDashboard(),
        },
    ],
};
