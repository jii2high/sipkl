<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Models\PengajuanPkl;
use App\Models\PeriodePkl;
use App\Models\Perusahaan;
use App\States\Pengajuan\DiajukanKaprog;
use App\States\Pengajuan\Draft;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PengajuanController extends Controller
{
    public function index(): Response
    {
        $user = request()->user();
        $siswa = $user->siswaProfile;

        $pengajuanAktif = PengajuanPkl::where('siswa_id', $siswa->id)
            ->with(['perusahaan', 'periode', 'histories.actor'])
            ->latest()
            ->first();

        return Inertia::render('Siswa/Pengajuan/Index', [
            'pengajuan' => $pengajuanAktif,
        ]);
    }

    public function create(): Response|RedirectResponse
    {
        $user = request()->user();
        $siswa = $user->siswaProfile;

        $activePengajuan = PengajuanPkl::where('siswa_id', $siswa->id)
            ->whereIn('status', [Draft::class, DiajukanKaprog::class, 'draft'])
            ->first();

        if ($activePengajuan) {
            return redirect()->route('siswa.pengajuan.index')
                ->with('error', 'Anda masih memiliki pengajuan aktif.');
        }

        $perusahaan = Perusahaan::orderBy('nama')->get();
        $periodeAktif = PeriodePkl::where('status_aktif', true)->first();

        return Inertia::render('Siswa/Pengajuan/Create', [
            'perusahaan' => $perusahaan,
            'periode' => $periodeAktif,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        $siswa = $user->siswaProfile;

        $validated = $request->validate([
            'perusahaan_id' => ['required', 'exists:perusahaan,id'],
        ], [
            'perusahaan_id.required' => 'Pilih perusahaan tujuan PKL.',
            'perusahaan_id.exists' => 'Perusahaan tidak ditemukan.',
        ]);

        $activePengajuan = PengajuanPkl::where('siswa_id', $siswa->id)
            ->whereIn('status', [Draft::class, DiajukanKaprog::class, 'draft'])
            ->first();

        if ($activePengajuan) {
            throw ValidationException::withMessages([
                'perusahaan_id' => 'Anda masih memiliki pengajuan aktif.',
            ]);
        }

        $periode = PeriodePkl::where('status_aktif', true)->firstOrFail();

        $pengajuan = PengajuanPkl::create([
            'siswa_id' => $siswa->id,
            'perusahaan_id' => $validated['perusahaan_id'],
            'periode_id' => $periode->id,
        ]);

        $pengajuan->status->transitionTo(DiajukanKaprog::class);

        return redirect()->route('siswa.pengajuan.index')
            ->with('success', 'Pengajuan PKL berhasil dikirim.');
    }

    public function riwayat(): Response
    {
        $user = request()->user();
        $siswa = $user->siswaProfile;

        $riwayat = PengajuanPkl::where('siswa_id', $siswa->id)
            ->with(['perusahaan', 'periode', 'histories.actor'])
            ->latest()
            ->paginate(15);

        return Inertia::render('Siswa/Riwayat/Index', [
            'riwayat' => $riwayat,
        ]);
    }
}
