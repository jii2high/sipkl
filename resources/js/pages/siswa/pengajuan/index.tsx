import { Head, router, useForm } from '@inertiajs/react';
import { AlertCircle, ChevronRight, Download, FileText, Upload } from 'lucide-react';
import { useRef, useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { dashboard as siswaDashboard } from '@/routes/siswa';
import {
    download as laporanAkhirDownload,
    store as laporanAkhirStore,
} from '@/routes/siswa/laporan-akhir';
import {
    index as pengajuanIndex,
    store as pengajuanStore,
} from '@/routes/siswa/pengajuan';
import {
    download as suratBalasanDownload,
    store as suratBalasanStore,
} from '@/routes/siswa/surat-balasan';

const STATUS_LABEL: Record<string, string> = {
    draft: 'Draft',
    diajukan: 'Menunggu Verifikasi Kaprog',
    disetujui: 'Disetujui Kaprog',
    ditolak: 'Ditolak',
    resmi_pkl: 'Resmi PKL',
    selesai_pkl: 'Selesai PKL',
};

const STATUS_VARIANT: Record<string, 'default' | 'secondary' | 'destructive' | 'outline'> = {
    draft: 'secondary',
    diajukan: 'secondary',
    disetujui: 'default',
    ditolak: 'destructive',
    resmi_pkl: 'default',
    selesai_pkl: 'default',
};

export default function Pengajuan({ pengajuan, pengajuanDitolak, perusahaan, periode }: any) {
    // Form pengajuan baru
    const { data, setData, post, errors, processing } = useForm({ perusahaan_id: '' });

    // Pilih perusahaan — autofill info
    const perusahaanDipilih = perusahaan?.find((p: any) => String(p.id) === data.perusahaan_id);

    // Upload Surat Balasan
    const fileRef = useRef<HTMLInputElement>(null);
    const [uploading, setUploading] = useState(false);

    // Upload Laporan Akhir
    const laporanRef = useRef<HTMLInputElement>(null);
    const [uploadingLaporan, setUploadingLaporan] = useState(false);

    // Dialog catatan untuk pengajuan ditolak
    const [catatanBuka, setCatatanBuka] = useState<number | null>(null);

    // Status aktif: ada pengajuan non-ditolak → form locked
    const formLocked = pengajuan !== null;

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();

        if (!data.perusahaan_id) {
return;
}

        post(pengajuanStore().url);
    }

    function handleUploadBalasan() {
        const file = fileRef.current?.files?.[0];

        if (!file) {
return;
}

        if (file.type !== 'application/pdf') {
 alert('File harus PDF.');

 return; 
}

        if (file.size > 5 * 1024 * 1024) {
 alert('Maksimal 5 MB.');

 return; 
}

        setUploading(true);
        const fd = new FormData();
        fd.append('file', file);
        router.post(suratBalasanStore({ pengajuan: pengajuan.id }).url, fd, {
            preserveScroll: true,
            onFinish: () => {
                setUploading(false);

                if (fileRef.current) {
fileRef.current.value = '';
}
            },
        });
    }

    function handleUploadLaporan() {
        const file = laporanRef.current?.files?.[0];

        if (!file) {
return;
}

        if (file.type !== 'application/pdf') {
 alert('File harus PDF.');

 return; 
}

        if (file.size > 10 * 1024 * 1024) {
 alert('Maksimal 10 MB.');

 return; 
}

        setUploadingLaporan(true);
        const fd = new FormData();
        fd.append('file', file);
        router.post(laporanAkhirStore({ pengajuan: pengajuan.id }).url, fd, {
            preserveScroll: true,
            onFinish: () => {
                setUploadingLaporan(false);

                if (laporanRef.current) {
laporanRef.current.value = '';
}
            },
        });
    }

    return (
        <>
            <Head title="Pengajuan PKL" />
            <div className="mx-auto max-w-2xl space-y-6 p-4">
                <h2 className="text-2xl font-semibold">Pengajuan PKL</h2>

                {/* ─────────────────────────────────────────────
                    BAGIAN 1: FORM PENGAJUAN (di atas)
                    Locked jika sudah ada pengajuan aktif
                ───────────────────────────────────────────── */}
                {!periode && (
                    <div className="rounded-lg border p-6 text-center text-muted-foreground">
                        Belum ada periode PKL aktif untuk akun Anda.
                    </div>
                )}

                {periode && (
                    <div className="space-y-4 rounded-lg border p-5">
                        <div className="flex items-center justify-between">
                            <h3 className="font-semibold">Form Pengajuan PKL</h3>
                            <span className="text-sm text-muted-foreground">
                                Periode: <strong>{periode.nama}</strong>
                            </span>
                        </div>

                        {/* Status pengajuan aktif (jika ada) */}
                        {pengajuan && (
                            <div className="space-y-3">
                                <div className="flex items-center justify-between rounded-lg bg-muted/50 p-3">
                                    <div className="text-sm">
                                        <p className="font-medium">{pengajuan.perusahaan?.nama}</p>
                                        <p className="text-muted-foreground">{pengajuan.perusahaan?.alamat}</p>
                                    </div>
                                    <Badge variant={STATUS_VARIANT[pengajuan.status] ?? 'secondary'}>
                                        {STATUS_LABEL[pengajuan.status] ?? pengajuan.status}
                                    </Badge>
                                </div>

                                {/* Catatan penolakan (jika ada dari history sebelum disetujui) */}
                                {pengajuan.catatan_kaprog && (
                                    <div className="flex items-start gap-2 rounded-lg bg-yellow-50 p-3 text-sm text-yellow-800">
                                        <AlertCircle className="mt-0.5 size-4 shrink-0" />
                                        <div>
                                            <p className="font-medium">Catatan Kaprog:</p>
                                            <p>{pengajuan.catatan_kaprog}</p>
                                        </div>
                                    </div>
                                )}

                                <p className="text-xs text-muted-foreground">
                                    Form pengajuan terkunci karena pengajuan sudah masuk antrian atau disetujui.
                                </p>
                            </div>
                        )}

                        {/* Form aktif — tampil jika belum ada pengajuan aktif */}
                        {!formLocked && (
                            <form onSubmit={handleSubmit} className="space-y-4">
                                <div>
                                    <Label>Perusahaan Tujuan</Label>
                                    <Select
                                        value={data.perusahaan_id}
                                        onValueChange={(v) => setData('perusahaan_id', v)}
                                    >
                                        <SelectTrigger>
                                            <SelectValue placeholder="Pilih perusahaan" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {perusahaan.map((p: any) => (
                                                <SelectItem key={p.id} value={String(p.id)}>
                                                    {p.nama}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    {errors.perusahaan_id && (
                                        <p className="mt-1 text-sm text-destructive">{errors.perusahaan_id}</p>
                                    )}
                                </div>

                                {/* Autofill info perusahaan */}
                                {perusahaanDipilih && (
                                    <div className="rounded-md bg-muted/50 p-3 text-sm">
                                        <p><span className="text-muted-foreground">Alamat: </span>{perusahaanDipilih.alamat ?? '-'}</p>
                                        <p><span className="text-muted-foreground">Telepon: </span>{perusahaanDipilih.telepon ?? '-'}</p>
                                        <p><span className="text-muted-foreground">Email: </span>{perusahaanDipilih.email ?? '-'}</p>
                                    </div>
                                )}

                                <Button type="submit" disabled={processing || !data.perusahaan_id} className="w-full">
                                    {processing ? 'Mengirim...' : 'Ajukan ke Kaprog'}
                                </Button>
                            </form>
                        )}

                        {/* Upload Surat Balasan — muncul jika disetujui & surat pengantar terbit */}
                        {pengajuan?.status === 'disetujui' && pengajuan?.exists_surat_pengantar && (
                            <div className="space-y-2 rounded-lg border border-blue-200 bg-blue-50 p-4">
                                <div className="flex items-center gap-2 text-sm font-medium text-blue-800">
                                    <FileText className="size-4" />
                                    Upload Surat Balasan dari Perusahaan
                                </div>
                                <p className="text-xs text-blue-600">
                                    Upload surat balasan untuk melanjutkan ke status Resmi PKL.
                                </p>
                                <div className="flex gap-2">
                                    <Input ref={fileRef} type="file" accept=".pdf" className="flex-1" />
                                    <Button size="sm" onClick={handleUploadBalasan} disabled={uploading}>
                                        <Upload className="mr-1 size-4" />
                                        {uploading ? 'Mengupload...' : 'Upload'}
                                    </Button>
                                </div>
                            </div>
                        )}

                        {pengajuan?.status === 'disetujui' && !pengajuan?.exists_surat_pengantar && (
                            <div className="rounded-lg bg-yellow-50 p-3 text-sm text-yellow-800">
                                Menunggu Surat Pengantar diterbitkan Hubin sebelum upload Surat Balasan.
                            </div>
                        )}

                        {/* Upload Laporan Akhir — muncul hanya jika resmi_pkl */}
                        {pengajuan?.status === 'resmi_pkl' && (
                            <div className="space-y-2 rounded-lg border border-green-200 bg-green-50 p-4">
                                <div className="flex items-center gap-2 text-sm font-medium text-green-800">
                                    <FileText className="size-4" />
                                    Laporan Akhir PKL
                                </div>
                                {pengajuan.laporan_akhir_uploaded_at ? (
                                    <div className="flex items-center gap-2">
                                        <p className="text-xs text-green-700">
                                            Diunggah pada{' '}
                                            {new Date(pengajuan.laporan_akhir_uploaded_at).toLocaleDateString('id-ID')}
                                        </p>
                                        <Button variant="outline" size="sm" asChild>
                                            <a href={laporanAkhirDownload({ pengajuan: pengajuan.id }).url} target="_blank" rel="noopener noreferrer">
                                                <Download className="mr-1 size-4" /> Download
                                            </a>
                                        </Button>
                                    </div>
                                ) : (
                                    <>
                                        <p className="text-xs text-green-700">
                                            Unggah laporan akhir menjelang PKL berakhir.
                                        </p>
                                        <div className="flex gap-2">
                                            <Input ref={laporanRef} type="file" accept=".pdf" className="flex-1" />
                                            <Button size="sm" onClick={handleUploadLaporan} disabled={uploadingLaporan}>
                                                <Upload className="mr-1 size-4" />
                                                {uploadingLaporan ? 'Mengupload...' : 'Upload'}
                                            </Button>
                                        </div>
                                    </>
                                )}
                                <Button variant="outline" size="sm" asChild>
                                    <a href={suratBalasanDownload({ pengajuan: pengajuan.id }).url} target="_blank" rel="noopener noreferrer">
                                        <Download className="mr-1 size-4" /> Download Surat Balasan
                                    </a>
                                </Button>
                            </div>
                        )}
                    </div>
                )}

                {/* ─────────────────────────────────────────────
                    BAGIAN 2: LIST PENGAJUAN DITOLAK (di bawah form)
                    Bisa isi ulang — pagination jika > 3
                ───────────────────────────────────────────── */}
                {pengajuanDitolak?.length > 0 && (
                    <div className="space-y-3">
                        <h3 className="font-semibold text-sm text-muted-foreground uppercase tracking-wide">
                            Pengajuan Ditolak — Bisa Diajukan Ulang
                        </h3>
                        {pengajuanDitolak.map((p: any) => (
                            <div
                                key={p.id}
                                className="rounded-lg border border-destructive/20 bg-destructive/5 p-4"
                            >
                                <div className="flex items-start justify-between gap-2">
                                    <div className="text-sm">
                                        <p className="font-medium">{p.perusahaan?.nama}</p>
                                        <p className="text-muted-foreground">{p.perusahaan?.alamat}</p>
                                    </div>
                                    <Badge variant="destructive">Ditolak</Badge>
                                </div>

                                <div className="mt-2 flex items-center gap-2">
                                    {p.catatan_kaprog ? (
                                        <button
                                            onClick={() => setCatatanBuka(catatanBuka === p.id ? null : p.id)}
                                            className="flex items-center gap-1 text-xs text-muted-foreground hover:text-foreground"
                                        >
                                            <AlertCircle className="size-3" />
                                            Lihat catatan penolakan
                                            <ChevronRight
                                                className={`size-3 transition-transform ${catatanBuka === p.id ? 'rotate-90' : ''}`}
                                            />
                                        </button>
                                    ) : (
                                        <span className="text-xs text-muted-foreground">Tanpa catatan</span>
                                    )}
                                </div>

                                {catatanBuka === p.id && p.catatan_kaprog && (
                                    <div className="mt-2 rounded bg-destructive/10 p-2 text-sm text-destructive">
                                        {p.catatan_kaprog}
                                    </div>
                                )}

                                {/* Tombol isi ulang hanya muncul jika tidak ada pengajuan aktif */}
                                {!formLocked && (
                                    <p className="mt-2 text-xs text-muted-foreground">
                                        Pilih perusahaan lain di form di atas untuk mengajukan ulang.
                                    </p>
                                )}
                            </div>
                        ))}
                    </div>
                )}
            </div>
        </>
    );
}

Pengajuan.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: siswaDashboard() },
        { title: 'Pengajuan PKL', href: pengajuanIndex() },
    ],
};
