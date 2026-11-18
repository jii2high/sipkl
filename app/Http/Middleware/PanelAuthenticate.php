<?php

namespace App\Http\Middleware;

use Filament\Http\Middleware\Authenticate as BaseAuthenticate;

/**
 * Panel Filament tanpa halaman login sendiri (T1.1): tamu diarahkan ke `/login`
 * kustom, bukan `/app/login` atau `/hubin/login`. Pemeriksaan `canAccessPanel`
 * bawaan Filament tetap berlaku.
 */
class PanelAuthenticate extends BaseAuthenticate
{
    protected function redirectTo($request): ?string
    {
        return route('login');
    }
}
