import { Head } from '@inertiajs/react';
import { dashboard as guruPembimbingDashboard } from '@/routes/guru-pembimbing';

export default function Dashboard() {
    return (
        <>
            <Head title="Dashboard Guru Pembimbing" />
            <div className="flex h-full flex-1 flex-col items-center justify-center gap-4 p-4">
                <h2 className="text-2xl font-semibold">
                    Dashboard Guru Pembimbing
                </h2>
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
            href: guruPembimbingDashboard(),
        },
    ],
};
