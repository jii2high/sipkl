<?php

namespace App\Http\Controllers\Hubin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hubin\KompetensiKeahlianRequest;
use App\Models\KompetensiKeahlian;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class KompetensiKeahlianController extends Controller
{
    public function index(Request $request): Response
    {
        $items = KompetensiKeahlian::query()
            ->when($request->input('search'), fn ($query, $search) => $query->where('nama', 'like', "%{$search}%"))
            ->orderBy('nama')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('hubin/kompetensi-keahlian/index', [
            'kompetensiKeahlians' => $items,
            'filters' => $request->only(['search']),
        ]);
    }

    public function store(KompetensiKeahlianRequest $request): RedirectResponse
    {
        KompetensiKeahlian::create($request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Kompetensi keahlian berhasil disimpan.',
        ]);

        return to_route('hubin.kompetensi-keahlian.index');
    }

    public function update(KompetensiKeahlianRequest $request, KompetensiKeahlian $kompetensiKeahlian): RedirectResponse
    {
        $kompetensiKeahlian->update($request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Kompetensi keahlian berhasil diperbarui.',
        ]);

        return to_route('hubin.kompetensi-keahlian.index');
    }

    public function destroy(KompetensiKeahlian $kompetensiKeahlian): RedirectResponse
    {
        $kompetensiKeahlian->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Kompetensi keahlian berhasil dihapus.',
        ]);

        return to_route('hubin.kompetensi-keahlian.index');
    }
}
