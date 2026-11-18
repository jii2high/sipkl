<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Pastikan ada peran aktif yang sah (dimiliki pengguna) sebelum akses panel
 * (BR-F1-5, TRD.md §7.2). Peran yang dipaksa tetapi tidak dimiliki → 403.
 */
class EnsureActiveRole
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $active = $request->session()->get('active_role');

        if ($user === null || ! is_string($active) || ! $user->hasRole($active)) {
            abort(403, __('pkl.role.not_active'));
        }

        return $next($request);
    }
}
