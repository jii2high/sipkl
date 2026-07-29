<?php

namespace App\Http\Controllers\Hubin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hubin\PerusahaanRequest;
use App\Models\Perusahaan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PerusahaanController extends Controller
{
    public function index(Request $request): Response
    {
        $perusahaans = Perusahaan::query()
            ->when($request->input('search'), fn ($query, $search) => $query->where('nama', 'like', "%{$search}%"))
            ->orderBy('nama')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('hubin/perusahaan/index', [
            'perusahaans' => $perusahaans,
            'filters' => $request->only(['search']),
        ]);
    }

    public function store(PerusahaanRequest $request): RedirectResponse
    {
        Perusahaan::create($request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Perusahaan master data berhasil disimpan.',
        ]);

        return to_route('hubin.perusahaan.index');
    }

    public function update(PerusahaanRequest $request, Perusahaan $perusahaan): RedirectResponse
    {
        $perusahaan->update($request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Perusahaan master data berhasil diperbarui.',
        ]);

        return to_route('hubin.perusahaan.index');
    }

    public function destroy(Perusahaan $perusahaan): RedirectResponse
    {
        $perusahaan->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Perusahaan master data berhasil dihapus.',
        ]);

        return to_route('hubin.perusahaan.index');
    }
}
