<?php

namespace App\Http\Controllers\Hubin;

use App\Exports\AkunExport;
use App\Exports\GuruTemplateExport;
use App\Exports\SiswaTemplateExport;
use App\Imports\GuruImport;
use App\Imports\SiswaImport;
use App\Http\Controllers\Controller;
use App\Models\GuruProfile;
use App\Models\KompetensiKeahlian;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Excel;

class ImportExportController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('kelola', GuruProfile::class);

        return Inertia::render('hubin/import-export/index');
    }

    public function exportAkun(): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $this->authorize('kelola', GuruProfile::class);

        return app(Excel::class)->download(new AkunExport(), 'akun-export.xlsx');
    }

    public function exportGuruTemplate(): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $this->authorize('kelola', GuruProfile::class);

        return app(Excel::class)->download(new GuruTemplateExport(), 'guru-template.xlsx');
    }

    public function exportSiswaTemplate(): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $this->authorize('kelola', GuruProfile::class);

        return app(Excel::class)->download(new SiswaTemplateExport(), 'siswa-template.xlsx');
    }

    public function importGuru(Request $request): RedirectResponse
    {
        $this->authorize('kelola', GuruProfile::class);

        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv'],
        ]);

        $import = new GuruImport();
        $import->import($request->file('file'));

        $message = 'Import guru selesai. Baris salah tidak disimpan.';
        if ($import->failures()->isNotEmpty()) {
            $message .= ' Cek file yang diunggah untuk baris gagal.';
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $message,
        ]);

        return back();
    }

    public function importSiswa(Request $request): RedirectResponse
    {
        $this->authorize('kelola', GuruProfile::class);

        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv'],
        ]);

        $import = new SiswaImport();
        $import->import($request->file('file'));

        $message = 'Import siswa selesai. Baris salah tidak disimpan.';
        if ($import->failures()->isNotEmpty()) {
            $message .= ' Cek file yang diunggah untuk baris gagal.';
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $message,
        ]);

        return back();
    }
}
