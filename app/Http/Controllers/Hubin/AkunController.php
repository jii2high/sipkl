<?php

namespace App\Http\Controllers\Hubin;

use App\Exports\AkunExport;
use App\Exports\TemplateGuruExport;
use App\Exports\TemplateSiswaExport;
use App\Http\Controllers\Controller;
use App\Imports\GuruImport;
use App\Imports\SiswaImport;
use App\Models\GuruProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AkunController extends Controller
{
    public function index(): Response
    {
        $guru = GuruProfile::with('user', 'kompetensiKeahlian')
            ->where('jabatan', 'kaprog')
            ->when(request('search'), fn ($q, $v) => $q->whereHas('user', fn ($q) => $q->where('name', 'like', "%{$v}%")))
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('hubin/kelola-akun/index', [
            'akun' => $guru,
        ]);
    }

    public function nonaktifkanKaprog(GuruProfile $guruProfile): RedirectResponse
    {
        $guruProfile->update([
            'jabatan' => 'guru_pembimbing',
            'kompetensi_keahlian_id' => null,
        ]);

        return back();
    }

    public function importGuru(Request $request): RedirectResponse
    {
        $request->validate(['file' => 'required|mimes:xlsx,xls']);

        $import = new GuruImport;
        Excel::import($import, $request->file('file'));

        if ($import->errors) {
            return back()->with('importErrors', $import->errors);
        }

        return back()->with('success', 'Import guru berhasil.');
    }

    public function importSiswa(Request $request): RedirectResponse
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
        return Excel::download(new AkunExport, 'akun.xlsx');
    }

    public function templateGuru(): BinaryFileResponse
    {
        return Excel::download(new TemplateGuruExport, 'template_guru.xlsx');
    }

    public function templateSiswa(): BinaryFileResponse
    {
        return Excel::download(new TemplateSiswaExport, 'template_siswa.xlsx');
    }
}
