<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Models\PengajuanPkl;
use App\Notifications\FormulirPenilaianTerbuka;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class LaporanAkhirController extends Controller
{
    /**
     * Upload Laporan Akhir PKL oleh siswa (BR12).
     * Setelah diupload, Formulir Penilaian terbuka untuk Guru Pembimbing (BR32).
     */
    public function store(Request $request, PengajuanPkl $pengajuan): RedirectResponse
    {
        $this->authorize('uploadLaporanAkhir', $pengajuan);

        abort_unless($pengajuan->status === 'resmi_pkl', 403, 'Laporan Akhir hanya dapat diunggah setelah status PKL Resmi.');

        $validated = $request->validate([
            'file' => [
                'required',
                'file',
                'mimes:pdf',
                'max:10240',
            ],
        ], [
            'file.required' => 'File laporan akhir wajib diunggah.',
            'file.mimes' => 'File harus berformat PDF.',
            'file.max' => 'Ukuran file maksimal 10 MB.',
        ]);

        if ($pengajuan->laporan_akhir_path) {
            Storage::delete($pengajuan->laporan_akhir_path);
        }

        $pengajuan->update([
            'laporan_akhir_path' => $request->file('file')->store('laporan-akhir'),
            'laporan_akhir_uploaded_at' => now(),
        ]);

        // BR32: buka gate penilaian untuk Guru Pembimbing terkait.
        $penugasan = $pengajuan->penugasanAktif;
        $penugasan?->guruPembimbing?->notify(new FormulirPenilaianTerbuka($pengajuan));

        return back()->with('sukses', 'Laporan Akhir PKL berhasil diunggah. Guru Pembimbing kini dapat mengisi Formulir Penilaian.');
    }

    /**
     * Download laporan akhir milik siswa.
     */
    public function download(PengajuanPkl $pengajuan): Response
    {
        $this->authorize('lihatSendiri', $pengajuan);

        abort_if(! $pengajuan->laporan_akhir_path || ! Storage::exists($pengajuan->laporan_akhir_path), 404);

        return response(Storage::get($pengajuan->laporan_akhir_path))
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="laporan-akhir-'.$pengajuan->id.'.pdf"');
    }
}
