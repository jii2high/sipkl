import { Head, router } from '@inertiajs/react';
import { CheckCircle, XCircle } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { dashboard as hubinDashboard } from '@/routes/hubin';
import { antrian as antrianRoute } from '@/routes/hubin/surat-tugas';

interface DraftItem {
    id: number;
    status: string;
    lingkup: string;
    berlaku_hingga: string | null;
    periode: { id: number; nama: string };
    kompetensi_keahlian: { id: number; nama: string } | null;
    requested_by: { id: number; name: string } | null;
}

interface Props {
    drafts: DraftItem[];
}

export default function AntrianMonitoring({ drafts }: Props) {
    // Dialog tolak
    const [dialogTolak, setDialogTolak] = useState(false);
    const [targetId, setTargetId] = useState<number | null>(null);
    const [catatan, setCatatan] = useState('');
    const [menolak, setMenolak] = useState(false);

    function bukaDialogTolak(id: number) {
        setTargetId(id);
        setCatatan('');
        setDialogTolak(true);
    }

    function setujui(id: number) {
        if (
            confirm(
                'Setujui dan terbitkan Surat Tugas Monitoring ini? Nomor surat dibuat otomatis.',
            )
        ) {
            router.post(
                `/hubin/surat-tugas/${id}/terbit`,
                {},
                {
                    onSuccess: () => {
                        // Inertia akan reload data dari server
                    },
                },
            );
        }
    }

    function tolak() {
        if (!targetId || !catatan.trim()) {
            return;
        }

        setMenolak(true);
        router.delete(`/hubin/surat-tugas/${targetId}/tolak`, {
            data: { catatan },
            onSuccess: () => {
                setDialogTolak(false);
                setMenolak(false);
            },
            onError: () => setMenolak(false),
        });
    }

    return (
        <>
            <Head title="Antrian Monitoring" />
            <div className="flex flex-col gap-4 p-4">
                <div>
                    <h2 className="text-2xl font-semibold">
                        Antrian Surat Tugas Monitoring
                    </h2>
                    <p className="text-sm text-muted-foreground">
                        Draft Surat Tugas yang diajukan Kaprog — setujui untuk
                        menerbitkan atau tolak dengan catatan.
                    </p>
                </div>

                <div className="overflow-hidden rounded-lg border">
                    <table className="w-full text-sm">
                        <thead className="text-left text-muted-foreground">
                            <tr className="border-b">
                                <th className="p-3">No</th>
                                <th className="p-3">Kaprog Peminta</th>
                                <th className="p-3">Periode</th>
                                <th className="p-3">Lingkup</th>
                                <th className="p-3">Berlaku s.d.</th>
                                <th className="p-3">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            {drafts.length === 0 && (
                                <tr>
                                    <td
                                        colSpan={6}
                                        className="p-6 text-center text-muted-foreground"
                                    >
                                        Tidak ada draft yang menunggu
                                        persetujuan.
                                    </td>
                                </tr>
                            )}
                            {drafts.map((d, idx) => (
                                <tr
                                    key={d.id}
                                    className="border-b last:border-0"
                                >
                                    <td className="p-3 text-muted-foreground">
                                        {idx + 1}
                                    </td>
                                    <td className="p-3 font-medium">
                                        {d.requested_by?.name ?? '-'}
                                    </td>
                                    <td className="p-3">{d.periode.nama}</td>
                                    <td className="p-3">
                                        {d.lingkup === 'global'
                                            ? 'Semua Jurusan'
                                            : (d.kompetensi_keahlian?.nama ??
                                              '-')}
                                    </td>
                                    <td className="p-3 text-muted-foreground">
                                        {d.berlaku_hingga ?? '-'}
                                    </td>
                                    <td className="p-3">
                                        <div className="flex gap-2">
                                            <Button
                                                size="sm"
                                                onClick={() => setujui(d.id)}
                                            >
                                                <CheckCircle className="mr-1 size-4" />
                                                Setujui
                                            </Button>
                                            <Button
                                                size="sm"
                                                variant="outline"
                                                className="text-destructive hover:text-destructive"
                                                onClick={() =>
                                                    bukaDialogTolak(d.id)
                                                }
                                            >
                                                <XCircle className="mr-1 size-4" />
                                                Tolak
                                            </Button>
                                        </div>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>

            {/* Dialog tolak dengan catatan wajib */}
            <Dialog open={dialogTolak} onOpenChange={setDialogTolak}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Tolak Draft Surat Tugas</DialogTitle>
                    </DialogHeader>
                    <div>
                        <Label>
                            Catatan Penolakan{' '}
                            <span className="text-destructive">*</span>
                        </Label>
                        <Textarea
                            value={catatan}
                            onChange={(e) => setCatatan(e.target.value)}
                            placeholder="Tuliskan alasan penolakan (min. 5 karakter)..."
                            rows={4}
                            className="mt-1"
                        />
                        <p className="mt-1 text-xs text-muted-foreground">
                            Catatan ini akan dikirim sebagai notifikasi ke
                            Kaprog peminta.
                        </p>
                    </div>
                    <DialogFooter>
                        <Button
                            variant="ghost"
                            onClick={() => setDialogTolak(false)}
                        >
                            Batal
                        </Button>
                        <Button
                            variant="destructive"
                            onClick={tolak}
                            disabled={menolak || catatan.trim().length < 5}
                        >
                            {menolak ? 'Menolak...' : 'Tolak Draft'}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}

AntrianMonitoring.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: hubinDashboard() },
        { title: 'Antrian Monitoring', href: antrianRoute() },
    ],
};
