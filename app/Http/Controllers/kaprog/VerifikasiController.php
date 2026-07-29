<?php

namespace App\Http\Controllers\Kaprog;

use App\Http\Controllers\Controller;
use App\Models\PengajuanPkl;
use App\Policies\PengajuanPklPolicy;
use App\States\Pengajuan\Draft;
use App\States\Pengajuan\SiapTerbit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class VerifikasiController extends Controller
{
    public function index(Request $request): Response
    {
        $pengajuan = PengajuanPkl::query()
            ->tap(fn ($q) => app(PengajuanPklPolicy::class)
                ->scopeUntukKaprog($q, $request->user()))
            ->with(['siswa', 'perusahaan', 'periode'])
            ->latest()
            ->paginate(15);

        return Inertia::render('Kaprog/Verifikasi/Index', [
            'pengajuan' => $pengajuan,
        ]);
    }

    public function show(Request $request, PengajuanPkl $pengajuan): Response
    {
        $this->authorize('view', $pengajuan);

        $pengajuan->load(['siswa', 'perusahaan', 'periode', 'histories.actor']);

        return Inertia::render('Kaprog/Verifikasi/Show', [
            'pengajuan' => $pengajuan,
        ]);
    }

    public function approve(Request $request, PengajuanPkl $pengajuan): RedirectResponse
    {
        $this->authorize('verifikasi', $pengajuan);

        $pengajuan->kaprog_id = $request->user()->id;
        $pengajuan->verified_kaprog_at = now();
        $pengajuan->status->transitionTo(SiapTerbit::class);
        $pengajuan->save();

        return back()->with('success', 'Pengajuan disetujui dan diteruskan ke Hubin.');
    }

    public function reject(Request $request, PengajuanPkl $pengajuan): RedirectResponse
    {
        $this->authorize('verifikasi', $pengajuan);

        $validated = $request->validate([
            'catatan_kaprog' => ['required', 'string', 'min:10'],
        ], [
            'catatan_kaprog.required' => 'Catatan penolakan wajib diisi.',
            'catatan_kaprog.min' => 'Catatan penolakan minimal 10 karakter.',
        ]);

        $pengajuan->kaprog_id = $request->user()->id;
        $pengajuan->catatan_kaprog = $validated['catatan_kaprog'];
        $pengajuan->status->transitionTo(Draft::class);
        $pengajuan->save();

        return back()->with('success', 'Pengajuan dikembalikan ke siswa dengan catatan.');
    }
}
