<?php

namespace App\Http\Controllers\Hubin;

use App\Http\Controllers\Controller;
use App\Models\PengajuanPkl;
use App\Models\PeriodePkl;
use App\Models\Perusahaan;
use App\Models\SuratPengantar;
use App\Notifications\SuratPengantarTerbit;
use App\Support\PenomoranSurat;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class SuratPengantarController extends Controller
{
    /**
     * Daftar perusahaan yang ada pengajuan berstatus 'disetujui' pada periode aktif/terpilih.
     */
    public function index(): Response
    {
        $this->authorize('viewAny', SuratPengantar::class);

        $periode = PeriodePkl::where('status_aktif', true)->first();

        $perusahaans = Perusahaan::query()
            ->when($periode, function ($q) use ($periode) {
                $q->whereHas('pengajuanPkls', fn ($q2) => $q2
                    ->where('periode_id', $periode->id)
                    ->where('status', 'disetujui')
                );
            })
            ->withCount(['pengajuanPkls as jumlah_siap' => fn ($q) => $q
                ->when($periode, fn ($q2) => $q2->where('periode_id', $periode->id))
                ->where('status', 'disetujui')
            ])
            ->get();

        return Inertia::render('hubin/surat-pengantar/index', [
            'perusahaans' => $perusahaans,
            'periodeAktif' => $periode,
        ]);
    }

    /**
     * Preview bundling per perusahaan+periode — dua tab: Kirim Surat & Balasan Masuk.
     */
    public function preview(Perusahaan $perusahaan, PeriodePkl $periode): Response
    {
        $this->authorize('viewAny', SuratPengantar::class);

        $pengajuans = PengajuanPkl::with(['siswa.user', 'siswa.kompetensiKeahlian'])
            ->where('perusahaan_id', $perusahaan->id)
            ->where('periode_id', $periode->id)
            ->where('status', 'disetujui')
            ->get();

        $balasanMasuk = PengajuanPkl::with(['siswa.user', 'suratBalasan'])
            ->where('perusahaan_id', $perusahaan->id)
            ->where('periode_id', $periode->id)
            ->where('status', 'resmi_pkl')
            ->get();

        $lewatKuota = $pengajuans->count() > $perusahaan->kuota_saran;

        return Inertia::render('hubin/surat-pengantar/preview', [
            'perusahaan' => $perusahaan,
            'periode' => $periode,
            'pengajuans' => $pengajuans,
            'balasanMasuk' => $balasanMasuk,
            'lewatKuota' => $lewatKuota,
        ]);
    }

    /**
     * Cetak PDF surat pengantar — hanya download, tidak mengubah status atau membuat record.
     */
    public function cetak(Perusahaan $perusahaan, PeriodePkl $periode): HttpResponse
    {
        $this->authorize('viewAny', SuratPengantar::class);

        $pengajuans = PengajuanPkl::with(['siswa.user', 'siswa.kompetensiKeahlian'])
            ->where('perusahaan_id', $perusahaan->id)
            ->where('periode_id', $periode->id)
            ->where('status', 'disetujui')
            ->get();

        // Buat objek surat sementara untuk preview PDF (tidak disimpan ke DB)
        $suratSementara = new SuratPengantar([
            'nomor_surat' => '___/421.5/SMKN 1-Cadisdik.Wil.II/___/'. now()->year,
            'tanggal_terbit' => now()->toDateString(),
        ]);
        $suratSementara->setRelation('perusahaan', $perusahaan);
        $suratSementara->setRelation('periode', $periode);
        $suratSementara->setRelation('pengajuans', $pengajuans);

        $pdf = Pdf::loadView('pdf.surat-pengantar', ['suratPengantar' => $suratSementara])
            ->setPaper('a4', 'portrait');

        return $pdf->download("surat-pengantar-preview-{$perusahaan->id}.pdf");
    }

    /**
     * Kirim surat pengantar — buat record, generate PDF final, simpan, ubah status, notifikasi.
     */
    public function kirim(Request $request, Perusahaan $perusahaan, PeriodePkl $periode): RedirectResponse
    {
        $this->authorize('create', SuratPengantar::class);

        $suratPengantar = DB::transaction(function () use ($request, $perusahaan, $periode) {
            $pengajuans = PengajuanPkl::with(['siswa.user', 'siswa.kompetensiKeahlian'])
                ->where('perusahaan_id', $perusahaan->id)
                ->where('periode_id', $periode->id)
                ->where('status', 'disetujui')
                ->get();

            abort_if($pengajuans->isEmpty(), 422, 'Tidak ada pengajuan yang siap untuk perusahaan ini.');

            // Generate nomor surat sequential dengan lockForUpdate
            $nomorSurat = PenomoranSurat::suratPengantar($periode->id);

            // Simpan record surat pengantar
            $surat = SuratPengantar::create([
                'nomor_surat' => $nomorSurat,
                'perusahaan_id' => $perusahaan->id,
                'periode_id' => $periode->id,
                'tanggal_terbit' => now()->toDateString(),
                'generated_by' => $request->user()->id,
            ]);

            // Attach semua pengajuan ke surat (pivot)
            $surat->pengajuans()->attach($pengajuans->pluck('id'));

            // Generate PDF final
            $surat->setRelation('perusahaan', $perusahaan);
            $surat->setRelation('periode', $periode);
            $surat->setRelation('pengajuans', $pengajuans);

            $pdf = Pdf::loadView('pdf.surat-pengantar', ['suratPengantar' => $surat])
                ->setPaper('a4', 'portrait');

            $namaFile = "surat-pengantar/{$surat->id}-{$nomorSurat}.pdf";
            $namaFile = preg_replace('/[\/\\\:*?"<>|]/', '_', $namaFile);
            Storage::put($namaFile, $pdf->output());

            $surat->update(['file_path' => $namaFile]);

            return $surat;
        });

        // Notifikasi ke semua siswa terkait, update status pengajuan
        $suratPengantar->pengajuans->each(function ($pengajuan) {
            $siswaUser = $pengajuan->siswa->user;
            $siswaUser?->notify(new SuratPengantarTerbit($pengajuan));
        });

        return redirect()->route('hubin.surat-pengantar.index')
            ->with('sukses', 'Surat pengantar berhasil diterbitkan.');
    }

    /**
     * Download PDF surat pengantar yang sudah tersimpan.
     */
    public function download(SuratPengantar $suratPengantar): HttpResponse
    {
        $this->authorize('view', $suratPengantar);

        abort_if(! $suratPengantar->file_path || ! Storage::exists($suratPengantar->file_path), 404);

        return response(Storage::get($suratPengantar->file_path))
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="surat-pengantar-'.$suratPengantar->id.'.pdf"');
    }

    /**
     * Tab Balasan Masuk — daftar pengajuan resmi_pkl per perusahaan+periode untuk aksi tolak pasca-fakta.
     */
    public function balasanMasuk(Perusahaan $perusahaan, PeriodePkl $periode): Response
    {
        $this->authorize('viewAny', SuratPengantar::class);

        $pengajuans = PengajuanPkl::with(['siswa.user', 'suratBalasan'])
            ->where('perusahaan_id', $perusahaan->id)
            ->where('periode_id', $periode->id)
            ->where('status', 'resmi_pkl')
            ->get();

        return Inertia::render('hubin/surat-pengantar/balasan-masuk', [
            'perusahaan' => $perusahaan,
            'periode' => $periode,
            'pengajuans' => $pengajuans,
        ]);
    }

    /**
     * Tolak surat balasan pasca-fakta (BR7) — status kembali ke disetujui.
     */
    public function tolakBalasan(Request $request, PengajuanPkl $pengajuan): RedirectResponse
    {
        $this->authorize('tolakBalasan', $pengajuan);

        $validated = $request->validate([
            'catatan' => 'required|string|min:15',
        ]);

        DB::transaction(function () use ($pengajuan, $validated, $request) {
            // Hapus record surat balasan
            $pengajuan->suratBalasan?->delete();

            // Kembali ke status disetujui agar siswa bisa upload ulang
            $pengajuan->status = 'disetujui';
            $pengajuan->catatan_kaprog = null; // bersihkan catatan sebelumnya agar tidak membingungkan
            $pengajuan->save();

            // Catat catatan penolakan ke history via observer (sudah otomatis)
            // Tapi kita perlu simpan catatan hubin ke history manual karena observer
            // hanya menyimpan catatan_kaprog
            $pengajuan->histories()->create([
                'actor_id' => $request->user()->id,
                'status_dari' => 'resmi_pkl',
                'status_ke' => 'disetujui',
                'catatan' => '[Hubin] Surat balasan ditolak: '.$validated['catatan'],
                'created_at' => now(),
            ]);
        });

        // Notifikasi ke siswa
        $pengajuan->siswa->user?->notify(new \App\Notifications\SuratBalasanDitolak($pengajuan, $validated['catatan']));

        return back()->with('sukses', 'Surat balasan berhasil ditolak. Status pengajuan kembali ke Disetujui.');
    }
}
