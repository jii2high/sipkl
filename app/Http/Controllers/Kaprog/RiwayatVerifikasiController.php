<?php

namespace App\Http\Controllers\Kaprog;

use App\Http\Controllers\Controller;
use App\Models\PengajuanPkl;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Riwayat verifikasi Kaprog — daftar pengajuan yang sudah diverifikasi
 * (disetujui/ditolak) beserta catatan, tanpa tombol aksi (BR4).
 */
class RiwayatVerifikasiController extends Controller
{
    public function index(): Response
    {
        $guruProfile = request()->user()->guruProfile;

        $pengajuan = PengajuanPkl::with(['siswa.user', 'perusahaan', 'periode'])
            ->whereHas('siswa', fn ($q) => $q->where('kompetensi_keahlian_id', $guruProfile->kompetensi_keahlian_id))
            ->whereIn('status', ['disetujui', 'ditolak'])
            ->when(request('search'), fn ($q, $v) => $q->whereHas('siswa.user', fn ($q2) => $q2->where('name', 'like', "%{$v}%")))
            ->when(request('status'), fn ($q, $v) => $q->where('status', $v))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('kaprog/riwayat-verifikasi/index', [
            'pengajuan' => $pengajuan,
        ]);
    }
}
