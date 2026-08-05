<?php

namespace App\Http\Controllers\Kaprog;

use App\Http\Controllers\Controller;
use App\Models\PengajuanPkl;
use App\Models\PenugasanPembimbing;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class PenugasanController extends Controller
{
    /**
     * Daftar pengajuan jurusan Kaprog + guru pembimbing untuk form assignment.
     */
    public function index(): Response
    {
        $kaprog = request()->user();
        $kompetensiKeahlianId = $kaprog->guruProfile?->kompetensi_keahlian_id;

        $pengajuans = PengajuanPkl::with(['siswa.user', 'siswa.kompetensiKeahlian', 'perusahaan', 'penugasanAktif.guruPembimbing'])
            ->whereHas('siswa', fn ($q) => $q->where('kompetensi_keahlian_id', $kompetensiKeahlianId))
            ->whereIn('status', ['disetujui', 'resmi_pkl'])
            ->orderByDesc('id')
            ->get();

        // Kaprog juga muncul sebagai opsi (self-assign), plus semua Guru Pembimbing.
        $guruPembimbings = User::role('guru_pembimbing')
            ->with('guruProfile')
            ->orderBy('name')
            ->get()
            ->push($kaprog);

        return Inertia::render('kaprog/penugasan/index', [
            'pengajuans' => $pengajuans,
            'guruPembimbings' => $guruPembimbings,
        ]);
    }

    /**
     * Assign Guru Pembimbing (BR10, eksklusif Kaprog). Penggantian mengarsipkan assignment lama (BR30).
     */
    public function store(Request $request, PengajuanPkl $pengajuan): RedirectResponse
    {
        $this->authorize('assign', $pengajuan);

        $validated = $request->validate([
            'guru_pembimbing_id' => 'required|exists:users,id',
        ]);

        DB::transaction(function () use ($pengajuan, $validated) {
            // BR30: arsipkan assignment aktif lama sebelum membuat yang baru.
            // update() pada relasi aman saat belum ada penugasan (tidak mengubah baris apa pun).
            $pengajuan->penugasanAktif()->update(['status' => 'nonaktif', 'ended_at' => now()]);

            PenugasanPembimbing::create([
                'guru_pembimbing_id' => $validated['guru_pembimbing_id'],
                'pengajuan_id' => $pengajuan->id,
                'assigned_by' => request()->user()->id,
            ]);
        });

        // Self-assign (Kaprog jadi pembimbing): tetap beri akses kunjungan.
        // Policy tetap membatasi hanya penugasan miliknya.
        if ((int) $validated['guru_pembimbing_id'] === auth()->id()) {
            auth()->user()->givePermissionTo(['kunjungan.isi', 'kunjungan.lihat']);
        }

        return back()->with('sukses', 'Guru pembimbing berhasil ditugaskan.');
    }
}
