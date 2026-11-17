<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureFirstLoginCompleted
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            return $next($request);
        }

        $user = Auth::user();
        $routeName = $request->route()?->getName();
        $allowedRoutes = ['login', 'verify-otp', 'force-password-reset', 'logout'];

        if ($user->is_first_login && session()->has('otp_pending') && ! in_array($routeName, $allowedRoutes, true)) {
            abort(403, 'Silakan selesaikan proses first-login terlebih dahulu.');
        }

        if ($user->is_first_login && session()->has('password_reset_pending') && ! in_array($routeName, ['force-password-reset', 'logout'], true)) {
            abort(403, 'Silakan atur password baru untuk menyelesaikan pendaftaran.');
        }

        return $next($request);
    }
}
