<?php

namespace App\Http\Controllers\Hubin;

use App\Http\Controllers\Controller;
use App\Models\IndikatorPenilaian;
use App\Models\SectionPenilaian;
use App\Models\SubSectionPenilaian;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Kelola indikator penilaian UMUM (kompetensi_keahlian_id null).
 * Indikator jurusan dikelola Kaprog (Kaprog\IndikatorJurusanController).
 */
class IndikatorPenilaianController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('hubin/indikator-penilaian/index', [
            'indikator' => IndikatorPenilaian::with('sections.subSections')
                ->whereNull('kompetensi_keahlian_id')
                ->when(request('search'), fn ($q, $v) => $q->where('nama', 'like', "%{$v}%"))
                ->orderBy('nomor_urut')
                ->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nomor_urut' => 'required|integer|min:1|max:99',
            'nama' => 'required|string|max:255',
            'deskripsi' => 'nullable|string|max:255',
        ]);

        IndikatorPenilaian::create($validated + ['kompetensi_keahlian_id' => null]);

        return redirect()->route('hubin.indikator-penilaian.index');
    }

    public function update(Request $request, IndikatorPenilaian $indikatorPenilaian): RedirectResponse
    {
        abort_unless($indikatorPenilaian->kompetensi_keahlian_id === null, 403);

        $validated = $request->validate([
            'nomor_urut' => 'required|integer|min:1|max:99',
            'nama' => 'required|string|max:255',
            'deskripsi' => 'nullable|string|max:255',
        ]);

        $indikatorPenilaian->update($validated);

        return redirect()->route('hubin.indikator-penilaian.index');
    }

    public function destroy(IndikatorPenilaian $indikatorPenilaian): RedirectResponse
    {
        abort_unless($indikatorPenilaian->kompetensi_keahlian_id === null, 403);

        $indikatorPenilaian->delete();

        return redirect()->route('hubin.indikator-penilaian.index');
    }

    public function storeSection(Request $request, IndikatorPenilaian $indikatorPenilaian): RedirectResponse
    {
        $validated = $request->validate([
            'nomor_urut' => 'required|integer|min:1|max:99',
            'nama' => 'required|string|max:255',
            'deskripsi' => 'nullable|string|max:255',
        ]);

        $indikatorPenilaian->sections()->create($validated);

        return redirect()->route('hubin.indikator-penilaian.index');
    }

    public function updateSection(Request $request, SectionPenilaian $section): RedirectResponse
    {
        abort_unless($section->indikatorPenilaian->kompetensi_keahlian_id === null, 403);

        $validated = $request->validate([
            'nomor_urut' => 'required|integer|min:1|max:99',
            'nama' => 'required|string|max:255',
            'deskripsi' => 'nullable|string|max:255',
        ]);

        $section->update($validated);

        return redirect()->route('hubin.indikator-penilaian.index');
    }

    public function destroySection(SectionPenilaian $section): RedirectResponse
    {
        abort_unless($section->indikatorPenilaian->kompetensi_keahlian_id === null, 403);

        $section->delete();

        return redirect()->route('hubin.indikator-penilaian.index');
    }

    public function storeSubSection(Request $request, SectionPenilaian $section): RedirectResponse
    {
        abort_unless($section->indikatorPenilaian->kompetensi_keahlian_id === null, 403);

        $validated = $request->validate([
            'nomor_urut' => 'required|integer|min:1|max:99',
            'nama' => 'required|string|max:255',
            'deskripsi' => 'nullable|string|max:255',
        ]);

        $section->subSections()->create($validated);

        return redirect()->route('hubin.indikator-penilaian.index');
    }

    public function updateSubSection(Request $request, SubSectionPenilaian $subSection): RedirectResponse
    {
        abort_unless($subSection->sectionPenilaian->indikatorPenilaian->kompetensi_keahlian_id === null, 403);

        $validated = $request->validate([
            'nomor_urut' => 'required|integer|min:1|max:99',
            'nama' => 'required|string|max:255',
            'deskripsi' => 'nullable|string|max:255',
        ]);

        $subSection->update($validated);

        return redirect()->route('hubin.indikator-penilaian.index');
    }

    public function destroySubSection(SubSectionPenilaian $subSection): RedirectResponse
    {
        abort_unless($subSection->sectionPenilaian->indikatorPenilaian->kompetensi_keahlian_id === null, 403);

        $subSection->delete();

        return redirect()->route('hubin.indikator-penilaian.index');
    }

    /**
     * Reorder level apa pun lewat payload `{ level: 'indikator|section|subSection', itemId, fromIndex, toIndex }`.
     */
    public function reorder(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'level' => 'required|in:indikator,section,subSection',
            'itemId' => 'required|integer',
            'fromIndex' => 'required|integer|min:0',
            'toIndex' => 'required|integer|min:0',
        ]);

        $siblings = match ($validated['level']) {
            'indikator' => IndikatorPenilaian::whereNull('kompetensi_keahlian_id')->orderBy('nomor_urut')->get(),
            'section' => SectionPenilaian::where('indikator_penilaian_id', $validated['itemId'])->orderBy('nomor_urut')->get(),
            default => SubSectionPenilaian::where('section_penilaian_id', $validated['itemId'])->orderBy('nomor_urut')->get(),
        };

        // ponytail: pivot manual — geser item, re-number 1..n (satu query per baris).
        $ids = $siblings->pluck('id')->all();
        array_splice($ids, $validated['toIndex'], 0, array_splice($ids, $validated['fromIndex'], 1));

        foreach (array_values($ids) as $index => $id) {
            $siblings->firstWhere('id', $id)?->update(['nomor_urut' => $index + 1]);
        }

        return redirect()->route('hubin.indikator-penilaian.index');
    }
}
