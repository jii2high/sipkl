<?php

namespace App\Http\Controllers;

use App\Models\VisitasiDokumentasi;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VisitasiDocumentationDownloadController extends Controller
{
    public function __invoke(int $id): StreamedResponse
    {
        $documentation = VisitasiDokumentasi::query()->findOrFail($id);
        Gate::authorize('download', $documentation);

        $path = $documentation->file_path;

        abort_unless(Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->download($path, basename($path));
    }
}
