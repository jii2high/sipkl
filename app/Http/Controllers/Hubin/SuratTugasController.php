<?php

namespace App\Http\Controllers\Hubin;

use App\Http\Controllers\Controller;
use App\Models\KompetensiKeahlian;
use App\Models\PeriodePkl;
use App\Models\SuratTugasMonitoring;
use App\Models\User;
use App\Notifications\SuratTugasTerbit;
use App\Support\PenomoranSurat;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class SuratTugasController extends Controller
{
    /**
     * Daftar Surat Tugas Monitoring (draft + terbit) + data untuk form terbitkan.
     */
    public function index(): Response
    {
        $suratTugass = SuratTugasMonitoring::with(['periode', 'kompetensiKeahlian', 'diterbitkanBy', 'requestedBy'])
            ->orderByDesc('id')
            ->get();

        return Inertia::render('hubin/surat-tugas/index', [
            'suratTugass' => $suratTugass,
            'periodes' => PeriodePkl::orderByDesc('id')->get(),
            'kompetensiKeahlians' => KompetensiKeahlian::orderBy('nama')->get(),
        ]);
    }

    /**
     * Terbitkan Surat Tugas (BR22, BR23) — nomor sequential BR18, PDF disimpan, notif dikirim.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'periode_id' => 'required|exists:periode_pkls,id',
            'lingkup' => 'required|in:global,scoped',
            'kompetensi_keahlian_id' => 'required_if:lingkup,scoped|nullable|exists:kompetensi_keahlian,id',
            'requested_by' => 'nullable|exists:users,id',
            'berlaku_hingga' => 'required|date|after_or_equal:today',
        ]);

        $suratTugas = DB::transaction(function () use ($validated, $request) {
            return SuratTugasMonitoring::create([
                'periode_id' => $validated['periode_id'],
                'lingkup' => $validated['lingkup'],
                'kompetensi_keahlian_id' => $validated['kompetensi_keahlian_id'] ?? null,
                'requested_by' => $validated['requested_by'] ?? null,
                'diterbitkan_by' => $request->user()->id,
                'nomor_surat' => PenomoranSurat::suratTugas($validated['periode_id']),
                'tanggal_terbit' => now()->toDateString(),
                'status' => 'aktif',
                'berlaku_hingga' => $validated['berlaku_hingga'],
            ]);
        });

        $suratTugas->generatePdf();

        // Notif ke Guru Pembimbing yang tercakup (+ Kaprog peminta kalau scoped)
        $suratTugas->penugasansTercakup()
            ->pluck('guruPembimbing')
            ->filter()
            ->unique('id')
            ->each(fn (User $pembimbing) => $pembimbing->notify(new SuratTugasTerbit($suratTugas)));

        if ($suratTugas->lingkup === 'scoped' && $suratTugas->requested_by) {
            User::find($suratTugas->requested_by)?->notify(new SuratTugasTerbit($suratTugas));
        }

        return redirect()->route('hubin.surat-tugas.index')
            ->with('sukses', 'Surat Tugas Monitoring berhasil diterbitkan.');
    }

    /**
     * Terbitkan draft (dibuat Kaprog scoped atau Hubin) — BR18: nomor sequential, BR22: aktif.
     */
    public function terbit(Request $request, SuratTugasMonitoring $suratTugas): RedirectResponse
    {
        abort_unless($suratTugas->status === 'draft', 422, 'Surat tugas ini bukan draft.');

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
            ->each(fn (User $pembimbing) => $pembimbing->notify(new SuratTugasTerbit($suratTugas)));

        return back()->with('sukses', 'Surat Tugas Monitoring berhasil diterbitkan.');
    }

    /**
     * Cabut / nonaktifkan Surat Tugas (BR22).
     */
    public function nonaktif(SuratTugasMonitoring $suratTugas): RedirectResponse
    {
        abort_if($suratTugas->status !== 'aktif', 422, 'Surat tugas sudah nonaktif.');

        $suratTugas->update(['status' => 'nonaktif']);

        return back()->with('sukses', 'Surat Tugas berhasil dinonaktifkan.');
    }

    /**
     * Download PDF arsip Surat Tugas.
     */
    public function download(SuratTugasMonitoring $suratTugas): HttpResponse
    {
        abort_if(! $suratTugas->file || ! Storage::exists($suratTugas->file), 404);

        return response(Storage::get($suratTugas->file))
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="surat-tugas-'.$suratTugas->id.'.pdf"');
    }
}
