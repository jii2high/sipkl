<?php

namespace App\Http\Controllers\Hubin;

use App\Exports\AkunExport;
use App\Exports\TemplateSiswaExport;
use App\Http\Controllers\Controller;
use App\Imports\SiswaImport;
use App\Models\KompetensiKeahlian;
use App\Models\SiswaProfile;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class KelolaSiswaController extends Controller
{
    public function index(): Response
    {
        $siswa = SiswaProfile::with('user', 'kompetensiKeahlian', 'periode')
            ->when(request('search'), fn ($q, $v) => $q->whereHas('user', fn ($q) => $q->where('name', 'like', "%{$v}%")))
            ->when(request('kompetensi_keahlian_id'), fn ($q, $v) => $q->where('kompetensi_keahlian_id', $v))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('hubin/kelola-siswa/index', [
            'siswa' => $siswa,
            'jurusan' => KompetensiKeahlian::orderBy('nama')->get(),
        ]);
    }

    public function destroy(User $user): RedirectResponse
    {
        abort_unless($user->siswaProfile !== null, 422, 'Bukan akun siswa.');

        $user->delete();

        return back()->with('success', 'Siswa dihapus (masuk recycle bin).');
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate(['file' => 'required|mimes:xlsx,xls']);

        $import = new SiswaImport;
        Excel::import($import, $request->file('file'));

        if ($import->errors) {
            return back()->with('importErrors', $import->errors);
        }

        return back()->with('success', 'Import siswa berhasil.');
    }

    public function export(): BinaryFileResponse
    {
        return Excel::download(new AkunExport, 'akun_siswa.xlsx');
    }

    public function template(): BinaryFileResponse
    {
        return Excel::download(new TemplateSiswaExport, 'template_siswa.xlsx');
    }
}
