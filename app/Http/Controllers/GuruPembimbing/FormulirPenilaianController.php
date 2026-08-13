<?php

namespace App\Http\Controllers\GuruPembimbing;

use App\Http\Controllers\Controller;
use App\Models\FormulirPenilaian;
use App\Models\IndikatorPenilaian;
use App\Models\PenugasanPembimbing;
use App\Models\SectionPenilaian;
use App\Models\SubSectionPenilaian;
use App\Notifications\PenilaianSubmitted;
use App\Support\SkalaPredikat;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class FormulirPenilaianController extends Controller
{
    /**
     * Daftar siswa bimbingan aktif + status Formulir Penilaian (BR11).
     */
    public function index(): Response
    {
        $penugasans = PenugasanPembimbing::with([
            'pengajuan.siswa.user',
            'pengajuan.siswa.kompetensiKeahlian',
            'pengajuan.perusahaan',
            'formulirPenilaian',
        ])
            ->where('guru_pembimbing_id', auth()->id())
            ->where('status', 'aktif')
            ->orderByDesc('id')
            ->get();

        return Inertia::render('guru-pembimbing/penilaian/index', [
            'penugasans' => $penugasans,
        ]);
    }

    /**
     * Formulir Penilaian — gated BR32 via Policy.
     */
    public function create(PenugasanPembimbing $penugasan): Response
    {
        $this->authorize('create', [FormulirPenilaian::class, $penugasan]);

        $penugasan->load(['pengajuan.siswa.user', 'pengajuan.siswa.kompetensiKeahlian', 'pengajuan.perusahaan', 'pengajuan.periode', 'formulirPenilaian.nilaiPenilaians']);

        $kompetensiId = $penugasan->pengajuan->siswa->kompetensi_keahlian_id;

        $indikatorUmum = IndikatorPenilaian::whereNull('kompetensi_keahlian_id')
            ->with('sections.subSections')
            ->orderBy('nomor_urut')
            ->get();

        $indikatorTeknis = IndikatorPenilaian::where('kompetensi_keahlian_id', $kompetensiId)
            ->with('sections.subSections')
            ->orderBy('nomor_urut')
            ->get();

        return Inertia::render('guru-pembimbing/penilaian/form', [
            'penugasan' => $penugasan,
            'indikatorUmum' => $indikatorUmum,
            'indikatorTeknis' => $indikatorTeknis,
        ]);
    }

    /**
     * Simpan & submit Formulir Penilaian (BR26, BR28, BR29, BR31).
     */
    public function store(Request $request, PenugasanPembimbing $penugasan): RedirectResponse
    {
        $this->authorize('create', [FormulirPenilaian::class, $penugasan]);

        $validated = $request->validate([
            'sakit' => 'required|integer|min:0|max:365',
            'izin' => 'required|integer|min:0|max:365',
            'alpa' => 'required|integer|min:0|max:365',
            'catatan_guru_pembimbing' => 'nullable|string|max:2000',
            'nama_pembimbing_industri' => 'nullable|string|max:150',
            'nilai' => 'required|array',
            'nilai.*.indikator_penilaian_id' => 'nullable|integer|exists:indikator_penilaians,id',
            'nilai.*.section_penilaian_id' => 'nullable|integer|exists:section_penilaians,id',
            'nilai.*.sub_section_penilaian_id' => 'nullable|integer|exists:sub_section_penilaians,id',
            'nilai.*.nilai' => 'nullable|numeric|min:0|max:100',
        ], [
            'sakit.required' => 'Jumlah hari sakit wajib diisi.',
            'izin.required' => 'Jumlah hari izin wajib diisi.',
            'alpa.required' => 'Jumlah hari alpa wajib diisi.',
            'nilai.required' => 'Minimal satu nilai wajib diisi.',
        ]);

        $indikatorIds = [];
        $sectionIds = [];
        $nilaiTerisi = [];
        $kategoriTerisi = [];

        foreach ((array) $validated['nilai'] as $leaf) {
            if ($leaf['indikator_penilaian_id'] !== null) {
                $indikatorIds[] = (int) $leaf['indikator_penilaian_id'];
            }

            if ($leaf['section_penilaian_id'] !== null) {
                $sectionIds[] = (int) $leaf['section_penilaian_id'];
            }

            if ($leaf['nilai'] === null || $leaf['nilai'] === '') {
                continue;
            }

            $nilaiTerisi[] = $leaf;

            $kkId = $leaf['indikator_penilaian_id'] !== null
                ? IndikatorPenilaian::find((int) $leaf['indikator_penilaian_id'])?->kompetensi_keahlian_id
                : ($leaf['section_penilaian_id'] !== null
                    ? SectionPenilaian::find((int) $leaf['section_penilaian_id'])?->indikatorPenilaian?->kompetensi_keahlian_id
                    : SubSectionPenilaian::find((int) $leaf['sub_section_penilaian_id'])?->sectionPenilaian?->indikatorPenilaian?->kompetensi_keahlian_id);

            $kategoriTerisi[] = $kkId === null ? 'umum' : 'jurusan';
        }

        // BR31: submit boleh partial, tapi minimal 1 leaf terisi per kelompok yang tampil.
        $kategoriTampil = IndikatorPenilaian::whereIn('id', $indikatorIds)
            ->orWhereHas('sections', fn ($q) => $q->whereIn('id', $sectionIds))
            ->get()
            ->map(fn ($i) => $i->kompetensi_keahlian_id === null ? 'umum' : 'jurusan')
            ->unique();

        if ($kategoriTampil->diff(collect($kategoriTerisi)->unique())->isNotEmpty()) {
            throw ValidationException::withMessages([
                'nilai' => 'Minimal satu nilai wajib diisi per kelompok indikator (Umum dan Jurusan).',
            ]);
        }

        $formulir = DB::transaction(function () use ($penugasan, $validated, $nilaiTerisi) {
            $formulir = $penugasan->formulirPenilaian()->create([
                'siswa_id' => $penugasan->pengajuan->siswa->user_id,
                'periode_pkl_id' => $penugasan->pengajuan->periode_id,
                'guru_pembimbing_id' => $penugasan->guru_pembimbing_id,
                'sakit' => $validated['sakit'],
                'izin' => $validated['izin'],
                'alpa' => $validated['alpa'],
                'catatan_guru_pembimbing' => $validated['catatan_guru_pembimbing'] ?? null,
                'nama_pembimbing_industri' => $validated['nama_pembimbing_industri'] ?? null,
            ]);

            foreach ($nilaiTerisi as $leaf) {
                $formulir->nilaiPenilaians()->create([
                    'indikator_penilaian_id' => $leaf['indikator_penilaian_id'] !== null ? (int) $leaf['indikator_penilaian_id'] : null,
                    'section_penilaian_id' => $leaf['section_penilaian_id'] !== null ? (int) $leaf['section_penilaian_id'] : null,
                    'sub_section_penilaian_id' => $leaf['sub_section_penilaian_id'] ?? null,
                    'nilai' => $leaf['nilai'],
                    'predikat' => SkalaPredikat::dari((float) $leaf['nilai']),
                ]);
            }

            // BR28: snapshot nilai akhir (2 tahap) + tandai submitted final (BR31).
            $formulir->update([
                'nilai_akhir' => $formulir->nilai_akhir,
                'submitted_at' => now(),
            ]);

            return $formulir;
        });

        $formulir->load('penugasan.pengajuan.siswa.user');
        $formulir->penugasan->pengajuan->siswa?->user?->notify(new PenilaianSubmitted($formulir));

        return redirect()->route('guru-pembimbing.penilaian.index')
            ->with('sukses', 'Formulir Penilaian berhasil disubmit. Nilai Akhir: '.$formulir->nilai_akhir);
    }
}
