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
            'nama' => 'required|string|max:255',
            'deskripsi' => 'nullable|string|max:255',
        ]);

        // Auto-assign nomor_urut = max + 1
        $maxNo = IndikatorPenilaian::whereNull('kompetensi_keahlian_id')->max('nomor_urut') ?? 0;

        IndikatorPenilaian::create($validated + [
            'kompetensi_keahlian_id' => null,
            'nomor_urut' => $maxNo + 1,
        ]);

        return redirect()->route('hubin.indikator-penilaian.index');
    }

    public function update(Request $request, IndikatorPenilaian $indikatorPenilaian): RedirectResponse
    {
        abort_unless($indikatorPenilaian->kompetensi_keahlian_id === null, 403);

        $validated = $request->validate([
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
            'nama' => 'required|string|max:255',
            'deskripsi' => 'nullable|string|max:255',
        ]);

        $maxNo = $indikatorPenilaian->sections()->max('nomor_urut') ?? 0;
        $indikatorPenilaian->sections()->create($validated + ['nomor_urut' => $maxNo + 1]);

        return redirect()->route('hubin.indikator-penilaian.index');
    }

    public function updateSection(Request $request, SectionPenilaian $section): RedirectResponse
    {
        abort_unless($section->indikatorPenilaian->kompetensi_keahlian_id === null, 403);

        $validated = $request->validate([
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
            'nama' => 'required|string|max:255',
            'deskripsi' => 'nullable|string|max:255',
        ]);

        $maxNo = $section->subSections()->max('nomor_urut') ?? 0;
        $section->subSections()->create($validated + ['nomor_urut' => $maxNo + 1]);

        return redirect()->route('hubin.indikator-penilaian.index');
    }

    public function updateSubSection(Request $request, SubSectionPenilaian $subSection): RedirectResponse
    {
        abort_unless($subSection->sectionPenilaian->indikatorPenilaian->kompetensi_keahlian_id === null, 403);

        $validated = $request->validate([
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
     * Reorder level apa pun lewat payload `{ level, ids[] }` dari dnd-kit.
     */
    public function reorder(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'level' => 'required|in:indikator,section,subSection',
            'itemId' => 'nullable|integer',
            'ids' => 'required|array',
            'ids.*' => 'integer',
        ]);

        foreach (array_values($validated['ids']) as $index => $id) {
            match ($validated['level']) {
                'indikator' => IndikatorPenilaian::where('id', $id)
                    ->whereNull('kompetensi_keahlian_id')
                    ->update(['nomor_urut' => $index + 1]),
                'section' => SectionPenilaian::where('id', $id)
                    ->update(['nomor_urut' => $index + 1]),
                default => SubSectionPenilaian::where('id', $id)
                    ->update(['nomor_urut' => $index + 1]),
            };
        }

        return redirect()->route('hubin.indikator-penilaian.index');
    }
}
