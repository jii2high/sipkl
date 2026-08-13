<?php

namespace App\Http\Controllers\Kaprog;

use App\Http\Controllers\Controller;
use App\Models\IndikatorPenilaian;
use App\Models\KompetensiKeahlian;
use App\Models\SectionPenilaian;
use App\Models\SubSectionPenilaian;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Kelola indikator penilaian per jurusan — EXCLUSIVE Kaprog (BR10-style).
 * Scope: hanya jurusan milik kaprog yang login (guru_profiles.kompetensi_keahlian_id).
 */
class IndikatorJurusanController extends Controller
{
    private function kompetensiKeahlianKaprog(): KompetensiKeahlian
    {
        $kk = request()->user()->guruProfile?->kompetensiKeahlian;

        abort_if($kk === null, 403, 'Akun kaprog belum terhubung ke kompetensi keahlian.');

        return $kk;
    }

    public function index(): Response
    {
        $kk = $this->kompetensiKeahlianKaprog();

        return Inertia::render('kaprog/indikator-jurusan/index', [
            'jurusan' => $kk,
            'indikator' => IndikatorPenilaian::with('sections.subSections')
                ->where('kompetensi_keahlian_id', $kk->id)
                ->orderBy('nomor_urut')
                ->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $kk = $this->kompetensiKeahlianKaprog();

        $validated = $request->validate([
            'nomor_urut' => 'required|integer|min:1|max:99',
            'nama' => 'required|string|max:255',
            'deskripsi' => 'nullable|string|max:255',
        ]);

        IndikatorPenilaian::create($validated + ['kompetensi_keahlian_id' => $kk->id]);

        return redirect()->route('kaprog.indikator-jurusan.index');
    }

    public function update(Request $request, IndikatorPenilaian $indikatorPenilaian): RedirectResponse
    {
        abort_unless($indikatorPenilaian->kompetensi_keahlian_id === $this->kompetensiKeahlianKaprog()->id, 403);

        $validated = $request->validate([
            'nomor_urut' => 'required|integer|min:1|max:99',
            'nama' => 'required|string|max:255',
            'deskripsi' => 'nullable|string|max:255',
        ]);

        $indikatorPenilaian->update($validated);

        return redirect()->route('kaprog.indikator-jurusan.index');
    }

    public function destroy(IndikatorPenilaian $indikatorPenilaian): RedirectResponse
    {
        abort_unless($indikatorPenilaian->kompetensi_keahlian_id === $this->kompetensiKeahlianKaprog()->id, 403);

        $indikatorPenilaian->delete();

        return redirect()->route('kaprog.indikator-jurusan.index');
    }

    public function storeSection(Request $request, IndikatorPenilaian $indikatorPenilaian): RedirectResponse
    {
        abort_unless($indikatorPenilaian->kompetensi_keahlian_id === $this->kompetensiKeahlianKaprog()->id, 403);

        $validated = $request->validate([
            'nomor_urut' => 'required|integer|min:1|max:99',
            'nama' => 'required|string|max:255',
            'deskripsi' => 'nullable|string|max:255',
        ]);

        $indikatorPenilaian->sections()->create($validated);

        return redirect()->route('kaprog.indikator-jurusan.index');
    }

    public function updateSection(Request $request, SectionPenilaian $section): RedirectResponse
    {
        abort_unless($section->indikatorPenilaian->kompetensi_keahlian_id === $this->kompetensiKeahlianKaprog()->id, 403);

        $validated = $request->validate([
            'nomor_urut' => 'required|integer|min:1|max:99',
            'nama' => 'required|string|max:255',
            'deskripsi' => 'nullable|string|max:255',
        ]);

        $section->update($validated);

        return redirect()->route('kaprog.indikator-jurusan.index');
    }

    public function destroySection(SectionPenilaian $section): RedirectResponse
    {
        abort_unless($section->indikatorPenilaian->kompetensi_keahlian_id === $this->kompetensiKeahlianKaprog()->id, 403);

        $section->delete();

        return redirect()->route('kaprog.indikator-jurusan.index');
    }

    public function storeSubSection(Request $request, SectionPenilaian $section): RedirectResponse
    {
        abort_unless($section->indikatorPenilaian->kompetensi_keahlian_id === $this->kompetensiKeahlianKaprog()->id, 403);

        $validated = $request->validate([
            'nomor_urut' => 'required|integer|min:1|max:99',
            'nama' => 'required|string|max:255',
            'deskripsi' => 'nullable|string|max:255',
        ]);

        $section->subSections()->create($validated);

        return redirect()->route('kaprog.indikator-jurusan.index');
    }

    public function updateSubSection(Request $request, SubSectionPenilaian $subSection): RedirectResponse
    {
        abort_unless($subSection->sectionPenilaian->indikatorPenilaian->kompetensi_keahlian_id === $this->kompetensiKeahlianKaprog()->id, 403);

        $validated = $request->validate([
            'nomor_urut' => 'required|integer|min:1|max:99',
            'nama' => 'required|string|max:255',
            'deskripsi' => 'nullable|string|max:255',
        ]);

        $subSection->update($validated);

        return redirect()->route('kaprog.indikator-jurusan.index');
    }

    public function destroySubSection(SubSectionPenilaian $subSection): RedirectResponse
    {
        abort_unless($subSection->sectionPenilaian->indikatorPenilaian->kompetensi_keahlian_id === $this->kompetensiKeahlianKaprog()->id, 403);

        $subSection->delete();

        return redirect()->route('kaprog.indikator-jurusan.index');
    }

    /**
     * Reorder dalam satu level, scope jurusan kaprog.
     */
    public function reorder(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'level' => 'required|in:indikator,section,subSection',
            'itemId' => 'required|integer',
            'fromIndex' => 'required|integer|min:0',
            'toIndex' => 'required|integer|min:0',
        ]);

        $kk = $this->kompetensiKeahlianKaprog();

        $siblings = match ($validated['level']) {
            'indikator' => IndikatorPenilaian::where('kompetensi_keahlian_id', $kk->id)->orderBy('nomor_urut')->get(),
            'section' => SectionPenilaian::where('indikator_penilaian_id', $validated['itemId'])->orderBy('nomor_urut')->get(),
            default => SubSectionPenilaian::where('section_penilaian_id', $validated['itemId'])->orderBy('nomor_urut')->get(),
        };

        $ids = $siblings->pluck('id')->all();
        array_splice($ids, $validated['toIndex'], 0, array_splice($ids, $validated['fromIndex'], 1));

        DB::transaction(function () use ($siblings, $ids) {
            foreach (array_values($ids) as $index => $id) {
                $siblings->firstWhere('id', $id)?->update(['nomor_urut' => $index + 1]);
            }
        });

        return redirect()->route('kaprog.indikator-jurusan.index');
    }
}
