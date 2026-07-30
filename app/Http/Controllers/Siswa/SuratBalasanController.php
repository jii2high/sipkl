<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Models\PengajuanPkl;
use App\Models\SuratBalasan;
use App\Notifications\SuratBalasanDiupload;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SuratBalasanController extends Controller
{
    /**
     * Upload Surat Balasan oleh siswa.
     * BR20: hanya bisa upload setelah Surat Pengantar ada.
     * BR7: setelah lolos validasi file, status langsung resmi_pkl.
     */
    public function store(Request $request, PengajuanPkl $pengajuan): RedirectResponse
    {
        $this->authorize('uploadBalasan', $pengajuan);

        // BR20: Cek keberadaan Surat Pengantar via relasi (bukan kolom status baru)
        abort_unless(
            $pengajuan->suratPengantars()->exists(),
            403,
            'Upload surat balasan belum tersedia. Surat Pengantar belum diterbitkan untuk pengajuan ini.'
        );

        $validated = $request->validate([
            'file' => [
                'required',
                'file',
                'mimes:pdf',
                'max:5120', // 5MB
            ],
        ], [
            'file.required' => 'File surat balasan wajib diunggah.',
            'file.mimes' => 'File harus berformat PDF.',
            'file.max' => 'Ukuran file maksimal 5 MB.',
        ]);

        // Hapus balasan lama jika ada (upload ulang)
        if ($pengajuan->suratBalasan) {
            Storage::delete($pengajuan->suratBalasan->file_path);
            $pengajuan->suratBalasan->delete();
        }

        // Simpan file ke storage
        $path = $request->file('file')->store('surat-balasan');

        // Buat record surat balasan
        SuratBalasan::create([
            'pengajuan_id' => $pengajuan->id,
            'file_path' => $path,
        ]);

        // BR7: Ubah status otomatis ke resmi_pkl
        $pengajuan->status = 'resmi_pkl';
        $pengajuan->save();

        // Notifikasi ke Hubin (opsional) dan konfirmasi ke siswa
        $pengajuan->siswa->user?->notify(new SuratBalasanDiupload($pengajuan));

        return back()->with('sukses', 'Surat balasan berhasil diunggah. Status PKL Anda kini Resmi PKL.');
    }

    /**
     * Download surat balasan milik siswa.
     */
    public function download(PengajuanPkl $pengajuan): \Illuminate\Http\Response
    {
        $this->authorize('lihatSendiri', $pengajuan);

        $suratBalasan = $pengajuan->suratBalasan;

        abort_if(! $suratBalasan || ! Storage::exists($suratBalasan->file_path), 404);

        return response(Storage::get($suratBalasan->file_path))
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="surat-balasan-'.$pengajuan->id.'.pdf"');
    }
}
