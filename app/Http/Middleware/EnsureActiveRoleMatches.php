<?php

namespace App\Http\Middleware;

use App\Services\ActiveRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Memastikan role aktif sesi termasuk role yang diizinkan route (TASKS M0.9, AC-06).
 *
 * Pemakaian: `->middleware('role:kaprog')` atau `'role:kaprog,guru_pembimbing'`.
 */
class EnsureActiveRoleMatches
{
    public function __construct(private readonly ActiveRole $activeRole) {}

    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $this->activeRole->user();

        abort_if($user === null, 403);
        abort_unless($user->is_active, 403);
        abort_unless($this->activeRole->is(...$roles), 403);

        return $next($request);
    }
}
