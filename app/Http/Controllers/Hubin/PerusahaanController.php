<?php

namespace App\Http\Controllers\Hubin;

use App\Http\Controllers\Controller;
use App\Models\Perusahaan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PerusahaanController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('hubin/perusahaan/index', [
            'perusahaan' => Perusahaan::query()
                ->when(request('search'), fn ($q, $v) => $q->where('nama', 'like', "%{$v}%"))
                ->paginate(10)
                ->withQueryString(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:150',
            'alamat' => 'nullable|string',
            'telp' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:150',
            'kuota_saran' => 'nullable|integer|min:1|max:6',
        ]);

        Perusahaan::create($validated);

        return redirect()->route('hubin.perusahaan.index');
    }

    public function update(Request $request, Perusahaan $perusahaan): RedirectResponse
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:150',
            'alamat' => 'nullable|string',
            'telp' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:150',
            'kuota_saran' => 'nullable|integer|min:1|max:6',
        ]);

        $perusahaan->update($validated);

        return redirect()->route('hubin.perusahaan.index');
    }

    public function destroy(Perusahaan $perusahaan): RedirectResponse
    {
        $perusahaan->delete();

        return redirect()->route('hubin.perusahaan.index');
    }
}
