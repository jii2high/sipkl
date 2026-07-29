<?php

namespace App\Http\Controllers\Hubin;

use App\Http\Controllers\Controller;
use App\Models\GuruProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AkunController extends Controller
{
    public function index(Request $request): Response
    {
        $guruProfiles = GuruProfile::with(['user', 'kompetensiKeahlian'])
            ->when($request->input('search'), fn ($query, $search) => $query->whereHas('user', fn ($q) => $q->where('name', 'like', "%{$search}%")))
            ->when($request->input('role'), fn ($query, $role) => $query->where('jabatan', $role))
            ->orderBy('jabatan')
            ->orderBy('nip')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('hubin/akun/index', [
            'guruProfiles' => $guruProfiles,
            'filters' => $request->only(['search', 'role']),
            'roles' => [
                ['value' => 'kaprog', 'label' => 'Kaprog'],
                ['value' => 'guru_pembimbing', 'label' => 'Guru Pembimbing'],
                ['value' => 'hubin', 'label' => 'Hubin'],
            ],
        ]);
    }

    public function nonaktifkanKaprog(GuruProfile $guruProfile): RedirectResponse
    {
        $this->authorize('kelola', $guruProfile);

        $guruProfile->update([
            'jabatan' => 'guru_pembimbing',
            'kompetensi_keahlian_id' => null,
        ]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Kaprog berhasil dinonaktifkan dan menjadi Guru Pembimbing.',
        ]);

        return back();
    }
}
