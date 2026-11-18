<?php

namespace App\Http\Middleware;

use Filament\Http\Middleware\AuthenticateSession as BaseAuthenticateSession;

/**
 * Sama seperti bawaan Filament, tetapi mengarahkan sesi kedaluwarsa ke `/login`
 * kustom karena panel tidak lagi punya halaman login (T1.1).
 */
class PanelAuthenticateSession extends BaseAuthenticateSession
{
    protected function redirectTo($request): ?string
    {
        return route('login');
    }
}
