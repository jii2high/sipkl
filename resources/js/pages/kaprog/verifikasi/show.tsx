import { Head, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import kaprogRoutes from '@/routes/kaprog';

const statusBadge: Record<string, string> = {
    draft: 'bg-muted text-muted-foreground',
    diajukan_kaprog: 'bg-yellow-100 text-yellow-800',
    ditolak_kaprog: 'bg-red-100 text-red-800',
    siap_terbit: 'bg-green-100 text-green-800',
};

export default function VerifikasiShow() {
    const { pengajuan } = usePage().props as any;
    const [catatan, setCatatan] = useState('');
    const [error, setError] = useState('');
    const [approveOpen, setApproveOpen] = useState(false);
    const [rejectOpen, setRejectOpen] = useState(false);

    function handleApprove() {
        router.post(
            kaprogRoutes.verifikasi.approve({ pengajuan: pengajuan.id }).url,
            {},
            { preserveScroll: true, onSuccess: () => setApproveOpen(false) },
        );
    }

    function handleReject() {
        if (!catatan || catatan.length < 10) {
            setError('Catatan penolakan minimal 10 karakter.');

            return;
        }

        router.post(
            kaprogRoutes.verifikasi.reject({ pengajuan: pengajuan.id }).url,
            { catatan_kaprog: catatan },
            {
                preserveScroll: true,
                onSuccess: () => {
                    setRejectOpen(false);
                    setCatatan('');
                    setError('');
                },
            },
        );
    }

    return (
        <>
            <Head title="Detail Pengajuan" />

            <div className="space-y-6 p-4">
                <h2 className="text-2xl font-semibold">Detail Pengajuan PKL</h2>

                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            {pengajuan.siswa?.user?.name}
                            <Badge
                                className={statusBadge[pengajuan.status] ?? ''}
                            >
                                {pengajuan.status}
                            </Badge>
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-3">
                        <p>
                            <span className="font-medium">NIS:</span>{' '}
                            {pengajuan.siswa?.nis}
                        </p>
                        <p>
                            <span className="font-medium">Kelas:</span>{' '}
                            {pengajuan.siswa?.kelas}
                        </p>
                        <p>
                            <span className="font-medium">Perusahaan:</span>{' '}
                            {pengajuan.perusahaan?.nama}
                        </p>
                        <p>
                            <span className="font-medium">
                                Alamat Perusahaan:
                            </span>{' '}
                            {pengajuan.perusahaan?.alamat}
                        </p>
                        <p>
                            <span className="font-medium">Periode:</span>{' '}
                            {pengajuan.periode?.nama}
                        </p>

                        {pengajuan.histories?.length > 0 && (
                            <div className="mt-4 space-y-2">
                                <h4 className="font-medium">Riwayat Status</h4>
                                {pengajuan.histories.map((h: any) => (
                                    <div
                                        key={h.id}
                                        className="flex items-center gap-2 text-sm text-muted-foreground"
                                    >
                                        <span className="size-1.5 rounded-full bg-muted-foreground" />
                                        <span>
                                            {h.status_dari ?? '-'} →{' '}
                                            {h.status_ke}
                                        </span>
                                        <span className="text-xs">
                                            oleh {h.actor?.name}
                                        </span>
                                    </div>
                                ))}
                            </div>
                        )}
                    </CardContent>
                </Card>

                {pengajuan.status === 'diajukan_kaprog' && (
                    <div className="flex gap-3">
                        <Dialog
                            open={approveOpen}
                            onOpenChange={setApproveOpen}
                        >
                            <DialogTrigger asChild>
                                <Button>Setujui</Button>
                            </DialogTrigger>
                            <DialogContent>
                                <DialogHeader>
                                    <DialogTitle>
                                        Konfirmasi Persetujuan
                                    </DialogTitle>
                                    <DialogDescription>
                                        Pengajuan akan langsung masuk antrian
                                        Hubin untuk penerbitan surat.
                                    </DialogDescription>
                                </DialogHeader>
                                <DialogFooter>
                                    <Button
                                        variant="outline"
                                        onClick={() => setApproveOpen(false)}
                                    >
                                        Batal
                                    </Button>
                                    <Button onClick={handleApprove}>
                                        Ya, Setujui
                                    </Button>
                                </DialogFooter>
                            </DialogContent>
                        </Dialog>

                        <Dialog open={rejectOpen} onOpenChange={setRejectOpen}>
                            <DialogTrigger asChild>
                                <Button variant="destructive">Tolak</Button>
                            </DialogTrigger>
                            <DialogContent>
                                <DialogHeader>
                                    <DialogTitle>Tolak Pengajuan</DialogTitle>
                                    <DialogDescription>
                                        Berikan alasan penolakan (minimal 10
                                        karakter).
                                    </DialogDescription>
                                </DialogHeader>
                                <div className="space-y-2">
                                    <Label htmlFor="catatan">
                                        Catatan Penolakan
                                    </Label>
                                    <textarea
                                        id="catatan"
                                        value={catatan}
                                        onChange={(e) => {
                                            setCatatan(e.target.value);
                                            setError('');
                                        }}
                                        placeholder="Alasan penolakan..."
                                        className="flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                                    />
                                    <InputError message={error} />
                                </div>
                                <DialogFooter>
                                    <Button
                                        variant="outline"
                                        onClick={() => setRejectOpen(false)}
                                    >
                                        Batal
                                    </Button>
                                    <Button
                                        variant="destructive"
                                        onClick={handleReject}
                                    >
                                        Tolak
                                    </Button>
                                </DialogFooter>
                            </DialogContent>
                        </Dialog>
                    </div>
                )}
            </div>
        </>
    );
}

VerifikasiShow.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/kaprog/dashboard' },
        { title: 'Verifikasi', href: kaprogRoutes.verifikasi.index() },
        { title: 'Detail', href: '' },
    ],
};
