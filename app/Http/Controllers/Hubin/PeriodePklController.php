<?php

namespace App\Http\Controllers\Hubin;

use App\Http\Controllers\Controller;
use App\Models\PeriodePkl;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PeriodePklController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('hubin/periode-pkl/index', [
            'periode' => PeriodePkl::query()
                ->when(request('search'), fn ($q, $v) => $q->where('nama', 'like', "%{$v}%"))
                ->orderBy('tanggal_mulai', 'desc')
                ->paginate(10)
                ->withQueryString(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:100',
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
            'status_aktif' => 'nullable|boolean',
        ]);

        PeriodePkl::create($validated);

        return redirect()->route('hubin.periode-pkl.index');
    }

    public function update(Request $request, PeriodePkl $periodePkl): RedirectResponse
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:100',
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
            'status_aktif' => 'nullable|boolean',
        ]);

        $periodePkl->update($validated);

        return redirect()->route('hubin.periode-pkl.index');
    }

    public function destroy(PeriodePkl $periodePkl): RedirectResponse
    {
        $periodePkl->delete();

        return redirect()->route('hubin.periode-pkl.index');
    }
}
