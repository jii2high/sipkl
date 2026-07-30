<?php

namespace App\Http\Controllers\Kaprog;

use App\Http\Controllers\Controller;
use App\Models\PengajuanPkl;
use App\Notifications\PengajuanDiverifikasi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class VerifikasiController extends Controller
{
    public function index(): Response
    {
        $guruProfile = request()->user()->guruProfile;

        $pengajuan = PengajuanPkl::with(['siswa.user', 'perusahaan', 'periode'])
            ->whereHas('siswa', fn ($q) => $q->where('kompetensi_keahlian_id', $guruProfile->kompetensi_keahlian_id))
            ->where('status', 'diajukan')
            ->when(request('search'), fn ($q, $v) => $q->whereHas('siswa.user', fn ($q2) => $q2->where('name', 'like', "%{$v}%")))
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('kaprog/verifikasi/index', [
            'pengajuan' => $pengajuan,
        ]);
    }

    public function show(PengajuanPkl $pengajuan): Response
    {
        $this->authorize('view', $pengajuan);

        $pengajuan->load(['siswa.user', 'perusahaan', 'periode', 'histories.actor']);

        return Inertia::render('kaprog/verifikasi/show', [
            'pengajuan' => $pengajuan,
        ]);
    }

    public function approve(Request $request, PengajuanPkl $pengajuan): RedirectResponse
    {
        $this->authorize('approve', $pengajuan);

        $pengajuan->kaprog_id = (int) $request->user()->id;
        $pengajuan->verified_kaprog_at = now();
        $pengajuan->status = 'disetujui';
        $pengajuan->save();

        $pengajuan->siswa->user->notify(new PengajuanDiverifikasi($pengajuan, 'disetujui'));

        return redirect()->route('kaprog.verifikasi.show', $pengajuan);
    }

    public function reject(Request $request, PengajuanPkl $pengajuan): RedirectResponse
    {
        $this->authorize('reject', $pengajuan);

        $validated = $request->validate([
            'catatan_kaprog' => 'required|string|min:15',
        ]);

        $pengajuan->kaprog_id = (int) $request->user()->id;
        $pengajuan->catatan_kaprog = $validated['catatan_kaprog'];
        $pengajuan->status = 'ditolak';
        $pengajuan->save();

        $pengajuan->siswa->user->notify(new PengajuanDiverifikasi($pengajuan, 'ditolak'));

        return redirect()->route('kaprog.verifikasi.show', $pengajuan);
    }
}
