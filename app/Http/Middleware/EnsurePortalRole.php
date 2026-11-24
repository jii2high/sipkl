<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Penjaga rute portal mobile: hanya peran yang disebut pada parameter rute yang
 * boleh masuk, dan peran itu harus sedang aktif (BR-F1-5, TRD.md §7.2).
 */
class EnsurePortalRole
{
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $user = $request->user();

        if ($user === null || session('active_role') !== $role || ! $user->hasRole($role)) {
            abort(403, __('pkl.role.not_active'));
        }

        return $next($request);
    }
}
