import { Head } from '@inertiajs/react';
import { dashboard as hubinDashboard } from '@/routes/hubin';

export default function Dashboard() {
    return (
        <>
            <Head title="Dashboard Hubin" />
            <div className="flex h-full flex-1 flex-col items-center justify-center gap-4 p-4">
                <h2 className="text-2xl font-semibold">Dashboard Hubin</h2>
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
            href: hubinDashboard(),
        },
    ],
};
