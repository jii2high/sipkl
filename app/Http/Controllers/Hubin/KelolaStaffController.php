<?php

namespace App\Http\Controllers\Hubin;

use App\Exports\TemplateGuruExport;
use App\Http\Controllers\Controller;
use App\Imports\GuruImport;
use App\Models\GuruProfile;
use App\Models\KompetensiKeahlian;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class KelolaStaffController extends Controller
{
    public function index(): Response
    {
        $guru = GuruProfile::with('user', 'kompetensiKeahlian')
            ->when(request('search'), fn ($q, $v) => $q->whereHas('user', fn ($q) => $q->where('name', 'like', "%{$v}%")))
            ->when(request('jabatan'), fn ($q, $v) => $q->where('jabatan', $v))
            ->orderBy('jabatan')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('hubin/kelola-staff/index', [
            'guru' => $guru,
            'jurusan' => KompetensiKeahlian::orderBy('nama')->get(),
        ]);
    }

    public function nonaktifkanKaprog(GuruProfile $guruProfile): RedirectResponse
    {
        $guruProfile->update([
            'jabatan' => 'guru_pembimbing',
            'kompetensi_keahlian_id' => null,
        ]);

        return back()->with('success', 'Kaprog dinonaktifkan — role otomatis jadi Guru Pembimbing (BR31).');
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate(['file' => 'required|mimes:xlsx,xls']);

        $import = new GuruImport;
        Excel::import($import, $request->file('file'));

        if ($import->errors) {
            return back()->with('importErrors', $import->errors);
        }

        return back()->with('success', 'Import guru berhasil.');
    }

    public function template(): BinaryFileResponse
    {
        return Excel::download(new TemplateGuruExport, 'template_guru.xlsx');
    }
}
