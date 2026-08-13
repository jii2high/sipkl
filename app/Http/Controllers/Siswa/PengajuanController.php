<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Models\PengajuanPkl;
use App\Models\PeriodePkl;
use App\Models\Perusahaan;
use App\Models\User;
use App\Notifications\PengajuanMasukAntrian;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PengajuanController extends Controller
{
    public function index(): Response
    {
        $siswa = request()->user()->siswaProfile;
        $periodeAktif = PeriodePkl::where('status_aktif', true)->first();

        // Periode pengajuan = periode pada profil siswa; fallback ke periode aktif
        $periodeProfil = $siswa->periode_id ? PeriodePkl::find($siswa->periode_id) : $periodeAktif;

        $pengajuanAktif = null;
        $pengajuanDitolak = collect();
        if ($periodeProfil) {
            $pengajuanAktif = PengajuanPkl::with(['perusahaan', 'periode', 'histories.actor'])
                ->where('siswa_id', $siswa->id)
                ->where('periode_id', $periodeProfil->id)
                ->whereNotIn('status', ['ditolak'])
                ->first();

            // List pengajuan ditolak periode ini — bisa ajukan ulang
            $pengajuanDitolak = PengajuanPkl::with(['perusahaan', 'periode', 'histories.actor'])
                ->where('siswa_id', $siswa->id)
                ->where('periode_id', $periodeProfil->id)
                ->where('status', 'ditolak')
                ->latest()
                ->get();
        }

        return Inertia::render('siswa/pengajuan/index', [
            'pengajuan' => $pengajuanAktif,
            'pengajuanDitolak' => $pengajuanDitolak,
            'perusahaan' => Perusahaan::orderBy('nama')->get(),
            'periode' => $periodeProfil,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $siswa = $request->user()->siswaProfile;

        $this->authorize('create', PengajuanPkl::class);

        // Periode pengajuan mengikuti periode pada profil siswa (sudah dipilih saat import/kelola)
        $periode = $siswa->periode_id
            ? PeriodePkl::where('status_aktif', true)->find($siswa->periode_id)
            : PeriodePkl::where('status_aktif', true)->firstOrFail();

        $validated = $request->validate([
            'perusahaan_id' => 'required|exists:perusahaan,id',
        ]);

        $pengajuan = PengajuanPkl::create([
            'siswa_id' => $siswa->id,
            'perusahaan_id' => $validated['perusahaan_id'],
            'periode_id' => $periode->id,
        ]);

        return redirect()->route('siswa.pengajuan.index');
    }

    public function submit(Request $request, PengajuanPkl $pengajuan): RedirectResponse
    {
        $this->authorize('submit', $pengajuan);

        $pengajuan->status = 'diajukan';
        $pengajuan->save();

        $kaprogs = User::role('kaprog')
            ->whereHas('guruProfile', fn ($q) => $q->where('kompetensi_keahlian_id', $request->user()->siswaProfile->kompetensi_keahlian_id))
            ->get();

        foreach ($kaprogs as $kaprog) {
            $kaprog->notify(new PengajuanMasukAntrian($pengajuan));
        }

        return redirect()->route('siswa.pengajuan.index');
    }

    public function riwayat(): Response
    {
        $siswa = request()->user()->siswaProfile;

        // Riwayat = pengajuan yang sudah selesai (closed) dari periode non-aktif
        $riwayat = PengajuanPkl::with(['perusahaan', 'periode'])
            ->where('siswa_id', $siswa->id)
            ->whereIn('status', ['resmi_pkl', 'selesai_pkl'])
            ->whereHas('periode', fn ($q) => $q->where('status_aktif', false))
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return Inertia::render('siswa/riwayat/index', [
            'riwayat' => $riwayat,
        ]);
    }
}
