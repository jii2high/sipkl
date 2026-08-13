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
    Recycle,
    Users,
} from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { NavFooter } from '@/components/nav-footer';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarGroup,
    SidebarGroupLabel,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/hooks/use-current-url';
import { dashboard } from '@/routes';
import type { Auth, NavItem } from '@/types';

interface NavSection {
    label: string;
    items: NavItem[];
}

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
    const { isCurrentUrl } = useCurrentUrl();
    const role = auth.roles?.[0];

    // Bangun sections berdasarkan role
    const sections: NavSection[] = [];

    // Section umum: Dashboard selalu ada
    const umumItems: NavItem[] = [
        { title: 'Dashboard', href: dashboard(), icon: LayoutGrid },
    ];

    if (role === 'siswa') {
        sections.push({
            label: 'PKL Saya',
            items: [
                ...umumItems,
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
            ],
        });
    }

    if (role === 'kaprog') {
        sections.push(
            {
                label: 'Dashboard',
                items: umumItems,
            },
            {
                label: 'PKL',
                items: [
                    {
                        title: 'Verifikasi PKL',
                        href: '/kaprog/verifikasi',
                        icon: ClipboardCheck,
                    },
                    {
                        title: 'Riwayat Verifikasi',
                        href: '/kaprog/riwayat-verifikasi',
                        icon: ListChecks,
                    },
                    {
                        title: 'Penugasan Pembimbing',
                        href: '/kaprog/penugasan',
                        icon: Users,
                    },
                ],
            },
            {
                label: 'Monitoring',
                items: [
                    {
                        title: 'Monitoring PKL',
                        href: '/kaprog/monitoring',
                        icon: Calendar,
                    },
                ],
            },
            {
                label: 'Indikator',
                items: [
                    {
                        title: 'Indikator Penilaian',
                        href: '/kaprog/indikator-jurusan',
                        icon: GraduationCap,
                    },
                ],
            },
        );
    }

    if (role === 'hubin') {
        sections.push(
            {
                label: 'Dashboard',
                items: umumItems,
            },
            {
                label: 'Master Data',
                items: [
                    {
                        title: 'Perusahaan',
                        href: '/hubin/perusahaan',
                        icon: Building2,
                    },
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
                ],
            },
            {
                label: 'Persuratan',
                items: [
                    {
                        title: 'Surat Pengantar',
                        href: '/hubin/surat-pengantar',
                        icon: FileText,
                    },
                    {
                        title: 'Antrian Monitoring',
                        href: '/hubin/surat-tugas/antrian',
                        icon: ClipboardCheck,
                    },
                ],
            },
            {
                label: 'Kelola Akun',
                items: [
                    {
                        title: 'Kelola Siswa',
                        href: '/hubin/kelola-siswa',
                        icon: Users,
                    },
                    {
                        title: 'Kelola Staff',
                        href: '/hubin/kelola-staff',
                        icon: GraduationCap,
                    },
                    {
                        title: 'Recycle Bin',
                        href: '/hubin/recycle-bin',
                        icon: Recycle,
                    },
                ],
            },
        );
    }

    if (role === 'guru_pembimbing') {
        sections.push({
            label: 'Pembimbingan',
            items: [
                ...umumItems,
                {
                    title: 'Kunjungan Monitoring',
                    href: '/guru-pembimbing/kunjungan',
                    icon: ClipboardCheck,
                },
                {
                    title: 'Penilaian PKL',
                    href: '/guru-pembimbing/penilaian',
                    icon: ListChecks,
                },
            ],
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
                {sections.map((section) => (
                    <SidebarGroup key={section.label} className="px-2 py-0">
                        <SidebarGroupLabel>{section.label}</SidebarGroupLabel>
                        <SidebarMenu>
                            {section.items.map((item) => (
                                <SidebarMenuItem key={item.title}>
                                    <SidebarMenuButton
                                        asChild
                                        isActive={isCurrentUrl(item.href)}
                                        tooltip={{ children: item.title }}
                                    >
                                        <Link href={item.href} prefetch>
                                            {item.icon && <item.icon />}
                                            <span>{item.title}</span>
                                        </Link>
                                    </SidebarMenuButton>
                                </SidebarMenuItem>
                            ))}
                        </SidebarMenu>
                    </SidebarGroup>
                ))}
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
