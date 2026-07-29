<?php

namespace App\Http\Controllers\Hubin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hubin\SectionPenilaianRequest;
use App\Models\IndikatorPenilaian;
use App\Models\SectionPenilaian;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SectionPenilaianController extends Controller
{
    public function index(Request $request): Response
    {
        $items = SectionPenilaian::with('indikatorPenilaian')
            ->when($request->input('search'), fn ($query, $search) => $query->where('deskripsi', 'like', "%{$search}%"))
            ->orderBy('nomor_urut')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('hubin/section-penilaian/index', [
            'sectionPenilaians' => $items,
            'indikatorOptions' => IndikatorPenilaian::orderBy('kategori')
                ->orderBy('nomor_urut')
                ->get()
                ->map(fn ($indikator) => [
                    'value' => $indikator->id,
                    'label' => ($indikator->kategori === 'umum' ? 'Umum' : 'Teknis Kejuruan') . ' - ' . $indikator->deskripsi,
                ])
                ->toArray(),
            'filters' => $request->only(['search']),
        ]);
    }

    public function store(SectionPenilaianRequest $request): RedirectResponse
    {
        SectionPenilaian::create($request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Section penilaian berhasil disimpan.',
        ]);

        return to_route('hubin.section-penilaian.index');
    }

    public function update(SectionPenilaianRequest $request, SectionPenilaian $sectionPenilaian): RedirectResponse
    {
        $sectionPenilaian->update($request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Section penilaian berhasil diperbarui.',
        ]);

        return to_route('hubin.section-penilaian.index');
    }

    public function destroy(SectionPenilaian $sectionPenilaian): RedirectResponse
    {
        $sectionPenilaian->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Section penilaian berhasil dihapus.',
        ]);

        return to_route('hubin.section-penilaian.index');
    }
}
