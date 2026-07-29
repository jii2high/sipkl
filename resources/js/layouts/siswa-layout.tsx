import { Link } from '@inertiajs/react';
import { Home, FileText, User, Menu } from 'lucide-react';
import type { ReactNode } from 'react';
import { Button } from '@/components/ui/button';
import { Sheet, SheetContent, SheetTrigger } from '@/components/ui/sheet';
import siswaRoutes, { dashboard as siswaDashboard } from '@/routes/siswa';

export default function SiswaLayout({ children }: { children: ReactNode }) {
    return (
        <div className="flex min-h-screen flex-col bg-background">
            <header className="bg-surface sticky top-0 z-40 flex h-14 items-center gap-4 border-b px-4">
                <Sheet>
                    <SheetTrigger asChild>
                        <Button variant="ghost" size="icon">
                            <Menu className="size-5" />
                        </Button>
                    </SheetTrigger>
                    <SheetContent side="left" className="w-64">
                        <nav className="flex flex-col gap-2 pt-6">
                            <Link
                                href={siswaDashboard().url}
                                className="rounded-lg px-3 py-2 text-sm hover:bg-muted"
                            >
                                Dashboard
                            </Link>
                            <Link
                                href={siswaRoutes.pengajuan.index().url}
                                className="rounded-lg px-3 py-2 text-sm hover:bg-muted"
                            >
                                Pengajuan PKL
                            </Link>
                            <Link
                                href={siswaRoutes.riwayat().url}
                                className="rounded-lg px-3 py-2 text-sm hover:bg-muted"
                            >
                                Riwayat PKL
                            </Link>
                        </nav>
                    </SheetContent>
                </Sheet>
                <span className="font-semibold">SI-PKL</span>
            </header>

            <main className="flex-1 p-4">{children}</main>

            <nav className="bg-surface sticky bottom-0 z-40 flex h-16 items-center justify-around border-t">
                <Link
                    href={siswaDashboard().url}
                    className="flex flex-col items-center gap-1 text-xs text-muted-foreground"
                >
                    <Home className="size-5" />
                    <span>Beranda</span>
                </Link>
                <Link
                    href={siswaRoutes.pengajuan.index().url}
                    className="flex flex-col items-center gap-1 text-xs text-muted-foreground"
                >
                    <FileText className="size-5" />
                    <span>Pengajuan</span>
                </Link>
                <Link
                    href="#"
                    className="flex flex-col items-center gap-1 text-xs text-muted-foreground"
                >
                    <User className="size-5" />
                    <span>Profil</span>
                </Link>
            </nav>
        </div>
    );
}
