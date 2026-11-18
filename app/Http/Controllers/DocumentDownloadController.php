<?php

namespace App\Http\Controllers;

use App\Services\DocumentService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Unduh PDF surat dari disk privat (TRD.md §7.4). URL hanya memuat jenis + id,
 * tidak pernah path asli. Otorisasi per request lewat `DocumentPolicy`.
 */
class DocumentDownloadController extends Controller
{
    public function __invoke(string $jenis, int $id): StreamedResponse
    {
        try {
            $class = DocumentService::modelClass($jenis);
        } catch (RuntimeException) {
            abort(404);
        }

        $document = $class::query()->findOrFail($id);

        Gate::authorize('download', $document);

        $path = $document->getAttribute('file_path');

        if (! is_string($path) || $path === '' || ! Storage::disk('local')->exists($path)) {
            abort(404);
        }

        $nomor = (string) $document->getAttribute('nomor_surat');
        $filename = $nomor !== '' ? str_replace(['/', ' '], '-', $nomor).'.pdf' : basename($path);

        return Storage::disk('local')->download($path, $filename);
    }
}
