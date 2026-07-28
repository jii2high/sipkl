<?php

namespace App\Http\Responses;

use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request)
    {
        return redirect()->route(match (true) {
            $request->user()->hasRole('siswa') => 'siswa.dashboard',
            $request->user()->hasRole('kaprog') => 'kaprog.dashboard',
            $request->user()->hasRole('hubin') => 'hubin.dashboard',
            default => 'guru-pembimbing.dashboard',
        });
    }
}
