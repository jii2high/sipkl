import { Head } from '@inertiajs/react';
import IndikatorTree from '@/components/indikator-tree';
import type { IndikatorItem } from '@/components/indikator-tree';
import { Badge } from '@/components/ui/badge';
import { dashboard as kaprogDashboard } from '@/routes/kaprog';

interface Props {
    jurusan: { id: number; nama: string; acronym: string };
    indikator: IndikatorItem[];
}

export default function Index({ jurusan, indikator }: Props) {
    return (
        <>
            <Head title="Indikator Penilaian Jurusan" />
            <div className="flex flex-col gap-4 p-4">
                <div className="flex items-center justify-between">
                    <div>
                        <h2 className="text-2xl font-semibold">
                            Indikator Penilaian Jurusan
                        </h2>
                        <p className="flex items-center gap-2 text-sm text-muted-foreground">
                            Khusus kompetensi keahlian Anda.
                            <Badge variant="secondary">{jurusan.nama}</Badge>
                        </p>
                    </div>
                </div>

                <IndikatorTree
                    indikator={indikator}
                    basePath="/kaprog/indikator-jurusan"
                />
            </div>
        </>
    );
}

Index.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: kaprogDashboard() },
        { title: 'Indikator Penilaian Jurusan' },
    ],
};
