<?php

namespace App\Http\Controllers\Hubin;

use App\Http\Controllers\Controller;
use App\Models\KompetensiKeahlian;
use App\Models\PeriodePkl;
use App\Models\SuratTugasMonitoring;
use App\Models\User;
use App\Notifications\DraftMonitoringDitolak;
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
            'langsung_terbit' => 'boolean',
        ]);

        $langsungTerbit = $request->has('langsung_terbit') ? $request->boolean('langsung_terbit') : true;

        $suratTugas = DB::transaction(function () use ($validated, $request, $langsungTerbit) {
            $data = [
                'periode_id' => $validated['periode_id'],
                'lingkup' => $validated['lingkup'],
                'kompetensi_keahlian_id' => $validated['kompetensi_keahlian_id'] ?? null,
                'requested_by' => $validated['requested_by'] ?? null,
                'berlaku_hingga' => $validated['berlaku_hingga'],
            ];

            if ($langsungTerbit) {
                $data['diterbitkan_by'] = $request->user()->id;
                $data['nomor_surat'] = PenomoranSurat::suratTugas($validated['periode_id']);
                $data['tanggal_terbit'] = now()->toDateString();
                $data['status'] = 'aktif';
            } else {
                // Simpan sebagai draft — Hubin yang tentukan terbit nanti
                $data['status'] = 'draft';
            }

            return SuratTugasMonitoring::create($data);
        });

        if ($langsungTerbit) {
            $suratTugas->generatePdf();

            // Notif ke Guru Pembimbing yang tercakup
            $suratTugas->penugasansTercakup()
                ->pluck('guruPembimbing')
                ->filter()
                ->unique('id')
                ->each(fn (User $pembimbing) => $pembimbing->notify(new SuratTugasTerbit($suratTugas)));

            if ($suratTugas->lingkup === 'scoped' && $suratTugas->requested_by) {
                User::find($suratTugas->requested_by)?->notify(new SuratTugasTerbit($suratTugas));
            }
        }

        $pesan = $langsungTerbit
            ? 'Surat Tugas Monitoring berhasil diterbitkan.'
            : 'Draft Surat Tugas berhasil disimpan.';

        return redirect()->route('hubin.surat-tugas.index')
            ->with('sukses', $pesan);
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

    /**
     * Antrian draft Surat Tugas yang diminta Kaprog — menunggu Hubin setujui/tolak.
     */
    public function antrian(): Response
    {
        $drafts = SuratTugasMonitoring::with(['periode', 'kompetensiKeahlian', 'requestedBy'])
            ->where('status', 'draft')
            ->whereNotNull('requested_by')
            ->orderByDesc('id')
            ->get();

        return Inertia::render('hubin/surat-tugas/antrian', [
            'drafts' => $drafts,
        ]);
    }

    /**
     * Tolak draft Surat Tugas dari Kaprog. Catatan wajib, notif ke peminta.
     */
    public function tolak(Request $request, SuratTugasMonitoring $suratTugas): RedirectResponse
    {
        abort_unless($suratTugas->status === 'draft', 422, 'Hanya draft yang bisa ditolak.');
        abort_unless($suratTugas->requested_by !== null, 422, 'Draft ini tidak berasal dari Kaprog.');

        $validated = $request->validate([
            'catatan' => 'required|string|min:5|max:500',
        ]);

        // Hapus draft + kirim notif ke Kaprog peminta
        $requestedBy = User::find($suratTugas->requested_by);
        $suratTugas->delete();

        // Notif sederhana lewat database notification
        if ($requestedBy) {
            $requestedBy->notify(new DraftMonitoringDitolak($validated['catatan']));
        }

        return redirect()->route('hubin.surat-tugas.antrian')
            ->with('sukses', 'Draft ditolak dan notifikasi dikirim ke Kaprog.');
    }
}
