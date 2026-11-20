<?php

namespace App\Http\Controllers;

use App\Models\LaporanVersion;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LaporanFileDownloadController extends Controller
{
    public function __invoke(int $version): StreamedResponse
    {
        $row = LaporanVersion::query()->findOrFail($version);
        Gate::authorize('download', $row);

        abort_unless(Storage::disk('local')->exists($row->file_path), 404);

        return Storage::disk('local')->download($row->file_path, basename($row->file_path));
    }
}
