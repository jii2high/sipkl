import type { Auth } from '@/types/auth';

declare module 'react' {
    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    interface InputHTMLAttributes<T> {
        passwordrules?: string;
    }
}

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            auth: Auth;
            sidebarOpen: boolean;
            notifikasi_belum_dibaca: number;
            notifikasi_terbaru: Array<{
                id: string;
                data: {
                    title: string;
                    message: string;
                    type: string;
                    pengajuan_id?: number;
                };
                read_at: string | null;
                created_at: string;
            }>;
            [key: string]: unknown;
        };
    }
}
