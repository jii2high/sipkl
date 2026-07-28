<?php

namespace App\Http\Controllers\Hubin;

use App\Http\Controllers\Controller;
use App\Models\KompetensiKeahlian;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class KompetensiKeahlianController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('hubin/kompetensi-keahlian/index', [
            'jurusan' => KompetensiKeahlian::query()
                ->when(request('search'), fn ($q, $v) => $q->where('nama', 'like', "%{$v}%"))
                ->paginate(10)
                ->withQueryString(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:100',
            'acronym' => 'nullable|string|max:20',
        ]);

        KompetensiKeahlian::create($validated);

        return redirect()->route('hubin.kompetensi-keahlian.index');
    }

    public function update(Request $request, KompetensiKeahlian $kompetensiKeahlian): RedirectResponse
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:100',
            'acronym' => 'nullable|string|max:20',
        ]);

        $kompetensiKeahlian->update($validated);

        return redirect()->route('hubin.kompetensi-keahlian.index');
    }

    public function destroy(KompetensiKeahlian $kompetensiKeahlian): RedirectResponse
    {
        $kompetensiKeahlian->delete();

        return redirect()->route('hubin.kompetensi-keahlian.index');
    }
}
