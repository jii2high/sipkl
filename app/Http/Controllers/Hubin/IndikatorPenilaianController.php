<?php

namespace App\Http\Controllers\Hubin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hubin\IndikatorPenilaianRequest;
use App\Models\IndikatorPenilaian;
use App\Models\KompetensiKeahlian;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class IndikatorPenilaianController extends Controller
{
    public function index(Request $request): Response
    {
        $items = IndikatorPenilaian::with('kompetensiKeahlian')
            ->when($request->input('search'), fn ($query, $search) => $query->where('deskripsi', 'like', "%{$search}%"))
            ->orderBy('kategori')
            ->orderBy('nomor_urut')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('hubin/indikator-penilaian/index', [
            'indikatorPenilaians' => $items,
            'kompetensiKeahlians' => KompetensiKeahlian::orderBy('nama')->get(),
            'filters' => $request->only(['search']),
        ]);
    }

    public function store(IndikatorPenilaianRequest $request): RedirectResponse
    {
        IndikatorPenilaian::create($request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Indikator penilaian berhasil disimpan.',
        ]);

        return to_route('hubin.indikator-penilaian.index');
    }

    public function update(IndikatorPenilaianRequest $request, IndikatorPenilaian $indikatorPenilaian): RedirectResponse
    {
        $indikatorPenilaian->update($request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Indikator penilaian berhasil diperbarui.',
        ]);

        return to_route('hubin.indikator-penilaian.index');
    }

    public function destroy(IndikatorPenilaian $indikatorPenilaian): RedirectResponse
    {
        $indikatorPenilaian->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Indikator penilaian berhasil dihapus.',
        ]);

        return to_route('hubin.indikator-penilaian.index');
    }
}
