<?php

namespace App\Http\Controllers\Hubin;

use App\Http\Controllers\Controller;
use App\Models\IndikatorPenilaian;
use App\Models\KompetensiKeahlian;
use App\Models\SectionPenilaian;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class IndikatorPenilaianController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('hubin/indikator-penilaian/index', [
            'indikator' => IndikatorPenilaian::with('sections', 'kompetensiKeahlian')
                ->when(request('search'), fn ($q, $v) => $q->where('deskripsi', 'like', "%{$v}%"))
                ->orderBy('nomor_urut')
                ->paginate(10)
                ->withQueryString(),
            'jurusan' => KompetensiKeahlian::all(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'kategori' => 'required|in:umum,teknis_kejuruan',
            'kompetensi_keahlian_id' => 'nullable|exists:kompetensi_keahlian,id',
            'nomor_urut' => 'required|integer|min:1',
            'deskripsi' => 'required|string|max:255',
        ]);

        IndikatorPenilaian::create($validated);

        return redirect()->route('hubin.indikator-penilaian.index');
    }

    public function update(Request $request, IndikatorPenilaian $indikatorPenilaian): RedirectResponse
    {
        $validated = $request->validate([
            'kategori' => 'required|in:umum,teknis_kejuruan',
            'kompetensi_keahlian_id' => 'nullable|exists:kompetensi_keahlian,id',
            'nomor_urut' => 'required|integer|min:1',
            'deskripsi' => 'required|string|max:255',
        ]);

        $indikatorPenilaian->update($validated);

        return redirect()->route('hubin.indikator-penilaian.index');
    }

    public function destroy(IndikatorPenilaian $indikatorPenilaian): RedirectResponse
    {
        $indikatorPenilaian->delete();

        return redirect()->route('hubin.indikator-penilaian.index');
    }

    public function storeSection(Request $request, IndikatorPenilaian $indikatorPenilaian): RedirectResponse
    {
        $validated = $request->validate([
            'nomor_urut' => 'required|integer|min:1',
            'deskripsi' => 'required|string|max:255',
        ]);

        $indikatorPenilaian->sections()->create($validated);

        return redirect()->route('hubin.indikator-penilaian.index');
    }

    public function updateSection(Request $request, SectionPenilaian $section): RedirectResponse
    {
        $validated = $request->validate([
            'nomor_urut' => 'required|integer|min:1',
            'deskripsi' => 'required|string|max:255',
        ]);

        $section->update($validated);

        return redirect()->route('hubin.indikator-penilaian.index');
    }

    public function destroySection(SectionPenilaian $section): RedirectResponse
    {
        $section->delete();

        return redirect()->route('hubin.indikator-penilaian.index');
    }
}
