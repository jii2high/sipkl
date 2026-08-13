<?php

namespace App\Http\Controllers\Hubin;

use App\Http\Controllers\Controller;
use App\Models\KompetensiKeahlian;
use App\Models\PeriodePkl;
use App\Models\Perusahaan;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Recycle bin Hubin — akun & master data yang dihapus (soft delete), bisa di-restore.
 */
class RecycleBinController extends Controller
{
    public function index(): Response
    {
        $akun = collect()
            ->merge(
                User::onlyTrashed()
                    ->whereHas('siswaProfile')
                    ->with(['siswaProfile.kompetensiKeahlian', 'siswaProfile.periode'])
                    ->get()
                    ->map(fn (User $u) => [
                        'id' => $u->id,
                        'name' => $u->name,
                        'email' => $u->email,
                        'role' => 'siswa',
                        'nis' => $u->siswaProfile->nis,
                        'detail' => $u->siswaProfile->kompetensiKeahlian?->acronym ?? '-',
                    ])
            )
            ->merge(
                User::onlyTrashed()
                    ->whereHas('guruProfile')
                    ->with('guruProfile')
                    ->get()
                    ->map(fn (User $u) => [
                        'id' => $u->id,
                        'name' => $u->name,
                        'email' => $u->email,
                        'role' => $u->guruProfile->jabatan,
                        'nis' => $u->guruProfile->nip,
                        'detail' => $u->guruProfile->jabatan,
                    ])
            )
            ->sortByDesc('name')
            ->values();

        return Inertia::render('hubin/recycle-bin/index', [
            'akun' => $akun,
            'perusahaan' => Perusahaan::onlyTrashed()->get(),
            'periode' => PeriodePkl::onlyTrashed()->get(),
            'kompetensiKeahlian' => KompetensiKeahlian::onlyTrashed()->get(),
        ]);
    }

    public function restore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'type' => 'required|in:akun,perusahaan,periode,kompetensiKeahlian',
            'id' => 'required|integer',
        ]);

        $model = match ($validated['type']) {
            'akun' => User::class,
            'perusahaan' => Perusahaan::class,
            'periode' => PeriodePkl::class,
            default => KompetensiKeahlian::class,
        };

        $item = $model::onlyTrashed()->findOrFail($validated['id']);
        $item->restore();

        return back()->with('sukses', 'Data berhasil dipulihkan.');
    }

    /**
     * Hapus permanen (force delete) — akun ikut menghapus profil & user.
     */
    public function forceDestroy(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'type' => 'required|in:akun,perusahaan,periode,kompetensiKeahlian',
            'id' => 'required|integer',
        ]);

        if ($validated['type'] === 'akun') {
            $user = User::onlyTrashed()->findOrFail($validated['id']);
            $user->siswaProfile()?->withTrashed()->first()?->forceDelete();
            $user->guruProfile()?->withTrashed()->first()?->forceDelete();
            $user->forceDelete();
        } else {
            $model = match ($validated['type']) {
                'perusahaan' => Perusahaan::class,
                'periode' => PeriodePkl::class,
                default => KompetensiKeahlian::class,
            };
            $model::onlyTrashed()->findOrFail($validated['id'])->forceDelete();
        }

        return back()->with('sukses', 'Data dihapus permanen.');
    }
}
