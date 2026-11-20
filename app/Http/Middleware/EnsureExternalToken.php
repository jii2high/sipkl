<?php

namespace App\Http\Middleware;

use App\Models\ExternalSession;
use App\Services\ExternalOtpService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guard portal eksternal (TRD.md §9.3): hanya sesi turunan magic link yang sah.
 * Semua kegagalan — tidak ada sesi, tidak valid, kedaluwarsa — memakai halaman
 * dan pesan yang sama agar keberadaan target tidak bocor (BR-F11-4).
 */
class EnsureExternalToken
{
    public function __construct(private readonly ExternalOtpService $otp) {}

    public function handle(Request $request, Closure $next): Response
    {
        $id = $request->session()->get('external_session_id');
        $session = is_numeric($id) ? ExternalSession::query()->find((int) $id) : null;

        if ($session === null || ! $this->otp->isUsable($session)) {
            return response()->view('eksternal.tidak-valid', [], 410);
        }

        $request->attributes->set('external_session', $session);

        return $next($request);
    }
}
