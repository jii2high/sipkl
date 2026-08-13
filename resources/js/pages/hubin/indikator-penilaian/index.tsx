import { Head } from '@inertiajs/react';
import IndikatorTree from '@/components/indikator-tree';
import type {IndikatorItem} from '@/components/indikator-tree';
import { dashboard as hubinDashboard } from '@/routes/hubin';

interface Props {
    indikator: IndikatorItem[];
}

export default function Index({ indikator }: Props) {
    return (
        <>
            <Head title="Indikator Penilaian Umum" />
            <div className="flex flex-col gap-4 p-4">
                <div className="flex items-center justify-between">
                    <div>
                        <h2 className="text-2xl font-semibold">
                            Indikator Penilaian Umum
                        </h2>
                        <p className="text-sm text-muted-foreground">
                            Berlaku untuk semua jurusan. Indikator jurusan
                            dikelola Kaprog masing-masing.
                        </p>
                    </div>
                </div>

                <IndikatorTree
                    indikator={indikator}
                    basePath="/hubin/indikator-penilaian"
                />
            </div>
        </>
    );
}

Index.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: hubinDashboard() },
        { title: 'Indikator Penilaian Umum' },
    ],
};