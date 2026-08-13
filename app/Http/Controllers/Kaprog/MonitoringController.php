<?php

namespace App\Http\Controllers\Kaprog;

use App\Http\Controllers\Controller;
use App\Models\PeriodePkl;
use App\Models\SuratTugasMonitoring;
use App\Notifications\SuratTugasTerbit;
use App\Support\PenomoranSurat;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Monitoring bulanan Kaprog (BR22): buat draft Surat Tugas scoped jurusan sendiri,
 * terbitkan (nomor otomatis BR18), dan lihat ringkasan surat tugas terbit.
 */
class MonitoringController extends Controller
{
    private function kompetensiKeahlianKaprog(): int
    {
        $kkId = request()->user()->guruProfile?->kompetensi_keahlian_id;

        abort_if($kkId === null, 403, 'Akun kaprog belum terhubung ke kompetensi keahlian.');

        return (int) $kkId;
    }

    public function index(): Response
    {
        $kkId = $this->kompetensiKeahlianKaprog();

        $drafts = SuratTugasMonitoring::with('periode')
            ->where('status', 'draft')
            ->where('lingkup', 'scoped')
            ->where('kompetensi_keahlian_id', $kkId)
            ->latest()
            ->get();

        $terbit = SuratTugasMonitoring::with('periode', 'kompetensiKeahlian', 'diterbitkanBy')
            ->whereIn('status', ['aktif', 'nonaktif'])
            ->where(fn ($q) => $q->where('lingkup', 'global')->orWhere('kompetensi_keahlian_id', $kkId))
            ->latest()
            ->get();

        return Inertia::render('kaprog/monitoring/index', [
            'drafts' => $drafts,
            'terbit' => $terbit,
            'periodes' => PeriodePkl::orderByDesc('id')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'periode_id' => 'required|exists:periode_pkls,id',
            'berlaku_hingga' => 'required|date|after_or_equal:today',
        ]);

        SuratTugasMonitoring::create([
            'periode_id' => $validated['periode_id'],
            'lingkup' => 'scoped',
            'kompetensi_keahlian_id' => $this->kompetensiKeahlianKaprog(),
            'requested_by' => $request->user()->id,
            'status' => 'draft',
            'berlaku_hingga' => $validated['berlaku_hingga'],
        ]);

        return redirect()->route('kaprog.monitoring.index')
            ->with('sukses', 'Draft Surat Tugas dibuat. Hubin akan menerbitkannya.');
    }

    public function destroy(SuratTugasMonitoring $suratTugas): RedirectResponse
    {
        abort_unless($suratTugas->status === 'draft' && $suratTugas->kompetensi_keahlian_id === $this->kompetensiKeahlianKaprog(), 403);

        $suratTugas->delete();

        return back()->with('sukses', 'Draft Surat Tugas dibatalkan.');
    }

    /**
     * Terbitkan draft (BR18: nomor sequential, BR22: berlaku aktif).
     */
    public function terbit(Request $request, SuratTugasMonitoring $suratTugas): RedirectResponse
    {
        abort_unless($suratTugas->status === 'draft' && $suratTugas->kompetensi_keahlian_id === $this->kompetensiKeahlianKaprog(), 403);

        $suratTugas = DB::transaction(function () use ($suratTugas, $request) {
            $suratTugas->update([
                'status' => 'aktif',
                'diterbitkan_by' => $request->user()->id,
                'nomor_surat' => PenomoranSurat::suratTugas($suratTugas->periode_id),
                'tanggal_terbit' => now()->toDateString(),
            ]);

            return $suratTugas;
        });

        $suratTugas->generatePdf();

        $suratTugas->penugasansTercakup()
            ->pluck('guruPembimbing')
            ->filter()
            ->unique('id')
            ->each(fn ($pembimbing) => $pembimbing->notify(new SuratTugasTerbit($suratTugas)));

        return back()->with('sukses', 'Surat Tugas berhasil diterbitkan.');
    }
}
