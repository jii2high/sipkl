<?php

namespace App\Http\Controllers;

use App\Models\DocumentSignature;
use App\Models\ExternalSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Unduhan laporan dari portal eksternal (F7). Hanya untuk sesi turunan tautan
 * pengesahan baris tanda tangan yang sama.
 */
class ExternalLaporanDownloadController extends Controller
{
    public function __invoke(Request $request, int $signature): StreamedResponse
    {
        $session = $request->attributes->get('external_session');

        abort_unless($session instanceof ExternalSession, 403);

        $link = $session->magicLink;
        $row = DocumentSignature::query()->findOrFail($signature);

        abort_unless(
            $link !== null
                && in_array($link->type, ['signer_step3', 'signer_step4'], true)
                && (int) $link->reference_id === (int) $row->getKey(),
            403,
        );

        $path = $row->laporanVersion?->file_path;

        abort_unless(is_string($path) && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->download($path);
    }
}
