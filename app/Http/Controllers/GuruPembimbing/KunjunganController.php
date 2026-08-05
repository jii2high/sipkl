<?php

namespace App\Http\Controllers\GuruPembimbing;

use App\Http\Controllers\Controller;
use App\Models\KunjunganMonitoring;
use App\Models\PenugasanPembimbing;
use App\Models\SuratTugasMonitoring;
use App\Models\User;
use App\Notifications\KunjunganBaru;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class KunjunganController extends Controller
{
    /**
     * Daftar penugasan aktif milik pembimbing + surat tugas aktif untuk form.
     */
    public function index(): Response
    {
        $penugasans = PenugasanPembimbing::with([
            'pengajuan.siswa.user',
            'pengajuan.siswa.kompetensiKeahlian',
            'pengajuan.perusahaan',
            'kunjunganMonitorings',
        ])
            ->where('guru_pembimbing_id', auth()->id())
            ->where('status', 'aktif')
            ->orderByDesc('id')
            ->get();

        $suratTugasAktif = SuratTugasMonitoring::aktif()
            ->with(['periode', 'kompetensiKeahlian'])
            ->orderByDesc('tanggal_terbit')
            ->get();

        return Inertia::render('guru-pembimbing/kunjungan/index', [
            'penugasans' => $penugasans,
            'suratTugasAktif' => $suratTugasAktif,
        ]);
    }

    /**
     * Isi Laporan Kunjungan — gated Surat Tugas aktif via Policy (BR22).
     */
    public function store(Request $request, PenugasanPembimbing $penugasan): RedirectResponse
    {
        $this->authorize('create', [KunjunganMonitoring::class, $penugasan]);

        $validated = $request->validate([
            'tanggal_kunjungan' => 'required|date',
            'catatan' => 'nullable|string|max:2000',
            'dokumentasi' => 'nullable|array',
            'dokumentasi.*' => 'file|max:10240|mimes:jpg,jpeg,png,pdf,mp4,mov',
        ]);

        // Query scope sama persis dengan Policy — memakai tanggal_kunjungan aktual sebagai batas berlaku.
        $suratTugas = SuratTugasMonitoring::query()
            ->where('periode_id', $penugasan->pengajuan->periode_id)
            ->where('status', 'aktif')
            ->where(function ($q) use ($penugasan) {
                $q->where('lingkup', 'global')
                    ->orWhere('kompetensi_keahlian_id', $penugasan->pengajuan->siswa->kompetensi_keahlian_id);
            })
            ->where(function ($q) use ($validated) {
                $q->whereNull('berlaku_hingga')
                    ->orWhereDate('berlaku_hingga', '>=', $validated['tanggal_kunjungan']);
            })
            ->orderByDesc('tanggal_terbit')
            ->first();

        abort_if(! $suratTugas, 403, 'Surat Tugas Monitoring belum aktif atau tidak berlaku untuk tanggal kunjungan tersebut.');

        $kunjungan = KunjunganMonitoring::create([
            'penugasan_id' => $penugasan->id,
            'surat_tugas_id' => $suratTugas->id,
            'tanggal_kunjungan' => $validated['tanggal_kunjungan'],
            'catatan' => $validated['catatan'] ?? null,
            'dokumentasi_path' => $request->hasFile('dokumentasi')
                ? collect($request->file('dokumentasi'))
                    ->map(fn ($file) => $file->store('kunjungan/'.$penugasan->pengajuan_id))
                    ->toJson()
                : null,
        ]);

        $kunjungan->load('penugasan.pengajuan.siswa.user');

        // Notif ke Siswa & Hubin
        $kunjungan->penugasan->pengajuan->siswa?->user?->notify(new KunjunganBaru($kunjungan));
        User::role('hubin')->get()->each(fn (User $hubin) => $hubin->notify(new KunjunganBaru($kunjungan)));

        return back()->with('sukses', 'Laporan kunjungan berhasil disimpan.');
    }
}
