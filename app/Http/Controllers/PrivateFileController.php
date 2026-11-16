<?php

namespace App\Http\Controllers;

use App\Services\PrivateFileStore;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Satu-satunya pintu unduh dokumen private (AC-49).
 *
 * Otorisasi per objek ditangani Policy (PrivateFilePolicy) lewat ability
 * `download-private-file`; default deny bila modul belum mendaftarkan prefix-nya.
 */
final class PrivateFileController extends Controller
{
    public function __invoke(Request $request, string $path, PrivateFileStore $store): StreamedResponse
    {
        abort_unless($store->isSafePath($path), 404);

        Gate::authorize('download-private-file', $path);

        abort_unless($store->exists($path), 404);

        return $store->download($path);
    }
}
