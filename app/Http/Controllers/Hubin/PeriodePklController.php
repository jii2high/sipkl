<?php

namespace App\Http\Controllers\Hubin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hubin\PeriodePklRequest;
use App\Models\PeriodePkl;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PeriodePklController extends Controller
{
    public function index(Request $request): Response
    {
        $periodes = PeriodePkl::query()
            ->when($request->input('search'), fn ($query, $search) => $query->where('nama', 'like', "%{$search}%"))
            ->orderByDesc('tanggal_mulai')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('hubin/periode-pkl/index', [
            'periodes' => $periodes,
            'filters' => $request->only(['search']),
        ]);
    }

    public function store(PeriodePklRequest $request): RedirectResponse
    {
        PeriodePkl::create($request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Periode PKL berhasil disimpan.',
        ]);

        return to_route('hubin.periode-pkl.index');
    }

    public function update(PeriodePklRequest $request, PeriodePkl $periodePkl): RedirectResponse
    {
        $periodePkl->update($request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Periode PKL berhasil diperbarui.',
        ]);

        return to_route('hubin.periode-pkl.index');
    }

    public function destroy(PeriodePkl $periodePkl): RedirectResponse
    {
        $periodePkl->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Periode PKL berhasil dihapus.',
        ]);

        return to_route('hubin.periode-pkl.index');
    }
}
