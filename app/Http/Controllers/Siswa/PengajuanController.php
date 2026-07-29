<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Models\PengajuanPkl;
use App\Models\PeriodePkl;
use App\Models\Perusahaan;
use App\Models\User;
use App\Notifications\PengajuanMasukAntrian;
use App\States\Pengajuan\DiajukanKaprog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PengajuanController extends Controller
{
    public function index(): Response
    {
        $siswaId = request()->user()->siswaProfile->id;
        $periodeAktif = PeriodePkl::where('status_aktif', true)->first();

        $pengajuanAktif = null;
        if ($periodeAktif) {
            $pengajuanAktif = PengajuanPkl::with(['perusahaan', 'periode', 'histories.actor'])
                ->where('siswa_id', $siswaId)
                ->where('periode_id', $periodeAktif->id)
                ->whereNotIn('status', ['ditolak'])
                ->first();
        }

        return Inertia::render('siswa/pengajuan/index', [
            'pengajuan' => $pengajuanAktif,
            'perusahaan' => Perusahaan::orderBy('nama')->get(),
            'periodeAktif' => $periodeAktif,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $siswa = $request->user()->siswaProfile;
        $periodeAktif = PeriodePkl::where('status_aktif', true)->firstOrFail();

        $this->authorize('create', PengajuanPkl::class);

        $validated = $request->validate([
            'perusahaan_id' => 'required|exists:perusahaan,id',
        ]);

        $pengajuan = PengajuanPkl::create([
            'siswa_id' => $siswa->id,
            'perusahaan_id' => $validated['perusahaan_id'],
            'periode_id' => $periodeAktif->id,
        ]);

        return redirect()->route('siswa.pengajuan.index');
    }

    public function submit(Request $request, PengajuanPkl $pengajuan): RedirectResponse
    {
        $this->authorize('submit', $pengajuan);

        $pengajuan->status->transitionTo(DiajukanKaprog::class);

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
        $siswaId = request()->user()->siswaProfile->id;
        $periodeAktif = PeriodePkl::where('status_aktif', true)->first();

        $riwayat = PengajuanPkl::with(['perusahaan', 'periode', 'histories.actor'])
            ->where('siswa_id', $siswaId)
            ->when($periodeAktif, fn ($q) => $q->where('periode_id', '!=', $periodeAktif->id))
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return Inertia::render('siswa/riwayat/index', [
            'riwayat' => $riwayat,
        ]);
    }
}
