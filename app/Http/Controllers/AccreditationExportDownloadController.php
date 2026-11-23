<?php

namespace App\Http\Controllers;

use App\Models\AccreditationExport;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Unduh snapshot ekspor akreditasi dari disk privat (TRD.md A16). Otorisasi
 * per request lewat `AccreditationExportPolicy`; URL hanya memuat id ekspor.
 */
class AccreditationExportDownloadController extends Controller
{
    public function __invoke(AccreditationExport $export): StreamedResponse
    {
        Gate::authorize('download', $export);

        $path = $export->snapshot_file_path;

        if (! is_string($path) || ! Storage::disk('local')->exists($path)) {
            abort(404);
        }

        $filename = 'akreditasi-'.$export->getKey().'.csv';

        return Storage::disk('local')->download($path, $filename);
    }
}
