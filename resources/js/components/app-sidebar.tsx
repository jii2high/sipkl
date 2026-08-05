import { Link, usePage } from '@inertiajs/react';
import {
    Bell,
    BookOpen,
    Building2,
    Calendar,
    ClipboardCheck,
    FileText,
    FolderGit2,
    GraduationCap,
    LayoutGrid,
    ListChecks,
    Users,
} from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import type { Auth, NavItem } from '@/types';

const footerNavItems: NavItem[] = [
    {
        title: 'Repository',
        href: 'https://github.com/laravel/react-starter-kit',
        icon: FolderGit2,
    },
    {
        title: 'Documentation',
        href: 'https://laravel.com/docs/starter-kits#react',
        icon: BookOpen,
    },
];

export function AppSidebar() {
    const { auth, notifikasi_belum_dibaca } = usePage<{
        auth: Auth;
        notifikasi_belum_dibaca: number;
    }>().props;
    const role = auth.roles?.[0];

    const mainNavItems: NavItem[] = [
        { title: 'Dashboard', href: dashboard(), icon: LayoutGrid },
    ];

    if (role === 'siswa') {
        mainNavItems.push(
            {
                title: 'Pengajuan PKL',
                href: '/siswa/pengajuan',
                icon: FileText,
            },
            {
                title: 'Riwayat PKL',
                href: '/siswa/riwayat',
                icon: ClipboardCheck,
            },
        );
    }

    if (role === 'kaprog') {
        mainNavItems.push(
            {
                title: 'Verifikasi PKL',
                href: '/kaprog/verifikasi',
                icon: ClipboardCheck,
            },
            {
                title: 'Penugasan Pembimbing',
                href: '/kaprog/penugasan',
                icon: Users,
            },
        );
    }

    if (role === 'hubin') {
        mainNavItems.push(
            { title: 'Perusahaan', href: '/hubin/perusahaan', icon: Building2 },
            {
                title: 'Periode PKL',
                href: '/hubin/periode-pkl',
                icon: Calendar,
            },
            {
                title: 'Kompetensi Keahlian',
                href: '/hubin/kompetensi-keahlian',
                icon: GraduationCap,
            },
            {
                title: 'Indikator Penilaian',
                href: '/hubin/indikator-penilaian',
                icon: ListChecks,
            },
            {
                title: 'Surat Pengantar',
                href: '/hubin/surat-pengantar',
                icon: FileText,
            },
            {
                title: 'Surat Tugas Monitoring',
                href: '/hubin/surat-tugas',
                icon: ClipboardCheck,
            },
            { title: 'Kelola Akun', href: '/hubin/kelola-akun', icon: Users },
        );
    }

    if (role === 'guru_pembimbing') {
        mainNavItems.push({
            title: 'Kunjungan Monitoring',
            href: '/guru-pembimbing/kunjungan',
            icon: ClipboardCheck,
        });
    }

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboard()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
                {notifikasi_belum_dibaca > 0 && (
                    <div className="flex items-center gap-2 px-3 py-2 text-sm">
                        <Bell className="size-4" />
                        <span>{notifikasi_belum_dibaca} notifikasi baru</span>
                    </div>
                )}
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={mainNavItems} />
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
