<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckFirstLogin
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->must_change_password) {
            $allowed = ['password.force-change', 'password.force-change.update', 'logout'];

            if (! in_array($request->route()->getName(), $allowed)) {
                return redirect()->route('password.force-change');
            }
        }

        return $next($request);
    }
}
