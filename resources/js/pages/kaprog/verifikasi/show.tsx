import { Head, useForm, router } from '@inertiajs/react';
import { useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
    DialogClose,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { dashboard as kaprogDashboard } from '@/routes/kaprog';
import {
    approve as verifikasiApprove,
    reject as verifikasiReject,
    index as verifikasiIndex,
} from '@/routes/kaprog/verifikasi';

const statusBadge: Record<string, string> = {
    draft: 'bg-muted text-muted-foreground',
    diajukan: 'bg-yellow-100 text-yellow-800',
    disetujui: 'bg-green-100 text-green-800',
    ditolak: 'bg-red-100 text-red-800',
};

export default function Detail({ pengajuan }: any) {
    const { data, setData, post, errors, processing } = useForm({
        catatan_kaprog: '',
    });
    const [rejectOpen, setRejectOpen] = useState(false);
    const [approveOpen, setApproveOpen] = useState(false);

    function handleApprove() {
        router.post(
            verifikasiApprove({ pengajuan: pengajuan.id }).url,
            {},
            {
                preserveScroll: true,
                onSuccess: () => setApproveOpen(false),
            },
        );
    }

    function handleReject(e: React.FormEvent) {
        e.preventDefault();
        post(verifikasiReject({ pengajuan: pengajuan.id }).url, {
            preserveScroll: true,
            onSuccess: () => setRejectOpen(false),
        });
    }

    return (
        <>
            <Head title="Detail Pengajuan" />

            <div className="mx-auto max-w-2xl space-y-6">
                <h2 className="text-2xl font-semibold">Detail Pengajuan PKL</h2>

                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center justify-between">
                            {pengajuan.siswa?.user?.name}
                            <Badge
                                className={statusBadge[pengajuan.status] || ''}
                            >
                                {pengajuan.status_label || pengajuan.status}
                            </Badge>
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="grid grid-cols-2 gap-2 text-sm">
                            <span className="text-muted-foreground">NIS</span>
                            <span className="font-medium">
                                {pengajuan.siswa?.nis}
                            </span>
                            <span className="text-muted-foreground">Kelas</span>
                            <span className="font-medium">
                                {pengajuan.siswa?.kelas}
                            </span>
                            <span className="text-muted-foreground">
                                Perusahaan
                            </span>
                            <span className="font-medium">
                                {pengajuan.perusahaan?.nama}
                            </span>
                            <span className="text-muted-foreground">
                                Alamat Perusahaan
                            </span>
                            <span className="font-medium">
                                {pengajuan.perusahaan?.alamat}
                            </span>
                            <span className="text-muted-foreground">
                                Periode
                            </span>
                            <span className="font-medium">
                                {pengajuan.periode?.nama}
                            </span>
                        </div>
                    </CardContent>
                </Card>

                {pengajuan.status === 'diajukan' && (
                    <div className="flex gap-3">
                        <Dialog
                            open={approveOpen}
                            onOpenChange={setApproveOpen}
                        >
                            <DialogTrigger asChild>
                                <Button className="flex-1">Setujui</Button>
                            </DialogTrigger>
                            <DialogContent>
                                <DialogHeader>
                                    <DialogTitle>
                                        Konfirmasi Persetujuan
                                    </DialogTitle>
                                </DialogHeader>
                                <p className="text-sm text-muted-foreground">
                                    Yakin menyetujui pengajuan PKL dari{' '}
                                    <strong>
                                        {pengajuan.siswa?.user?.name}
                                    </strong>
                                    ? Status akan berubah menjadi{' '}
                                    <strong>Disetujui / Siap Terbit</strong>.
                                </p>
                                <div className="flex justify-end gap-2">
                                    <DialogClose asChild>
                                        <Button variant="outline">Batal</Button>
                                    </DialogClose>
                                    <Button onClick={handleApprove}>
                                        Ya, Setujui
                                    </Button>
                                </div>
                            </DialogContent>
                        </Dialog>

                        <Dialog open={rejectOpen} onOpenChange={setRejectOpen}>
                            <DialogTrigger asChild>
                                <Button
                                    variant="destructive"
                                    className="flex-1"
                                >
                                    Tolak
                                </Button>
                            </DialogTrigger>
                            <DialogContent>
                                <DialogHeader>
                                    <DialogTitle>
                                        Konfirmasi Penolakan
                                    </DialogTitle>
                                </DialogHeader>
                                <form
                                    onSubmit={handleReject}
                                    className="space-y-4"
                                >
                                    <div className="space-y-2">
                                        <Label htmlFor="catatan">
                                            Alasan Penolakan
                                        </Label>
                                        <Textarea
                                            id="catatan"
                                            value={data.catatan_kaprog}
                                            onChange={(e) =>
                                                setData(
                                                    'catatan_kaprog',
                                                    e.target.value,
                                                )
                                            }
                                            placeholder="Minimal 15 karakter..."
                                            rows={3}
                                        />
                                        {errors.catatan_kaprog && (
                                            <p className="text-sm text-red-500">
                                                {errors.catatan_kaprog}
                                            </p>
                                        )}
                                    </div>
                                    <div className="flex justify-end gap-2">
                                        <DialogClose asChild>
                                            <Button
                                                type="button"
                                                variant="outline"
                                            >
                                                Batal
                                            </Button>
                                        </DialogClose>
                                        <Button
                                            type="submit"
                                            variant="destructive"
                                            disabled={processing}
                                        >
                                            Ya, Tolak
                                        </Button>
                                    </div>
                                </form>
                            </DialogContent>
                        </Dialog>
                    </div>
                )}

                {pengajuan.catatan_kaprog && (
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">
                                Catatan Kaprog
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p className="text-sm">
                                {pengajuan.catatan_kaprog}
                            </p>
                        </CardContent>
                    </Card>
                )}

                {pengajuan.histories && pengajuan.histories.length > 0 && (
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">
                                Riwayat Status
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-3">
                                {pengajuan.histories.map((h: any) => (
                                    <div
                                        key={h.id}
                                        className="flex items-start gap-3 text-sm"
                                    >
                                        <div className="mt-1.5 size-2 rounded-full bg-blue-500" />
                                        <div>
                                            <p className="font-medium">
                                                {h.status_dari || '-'} →{' '}
                                                {h.status_ke}
                                            </p>
                                            <p className="text-xs text-muted-foreground">
                                                {h.actor?.name} —{' '}
                                                {new Date(
                                                    h.created_at,
                                                ).toLocaleString('id-ID')}
                                            </p>
                                            {h.catatan && (
                                                <p className="mt-1 text-xs text-yellow-700">
                                                    {h.catatan}
                                                </p>
                                            )}
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </CardContent>
                    </Card>
                )}
            </div>
        </>
    );
}

Detail.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: kaprogDashboard() },
        { title: 'Verifikasi', href: verifikasiIndex() },
    ],
};
