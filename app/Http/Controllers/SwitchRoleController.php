<?php

namespace App\Http\Controllers;

use App\Actions\Auth\SwitchActiveRole;
use App\Enums\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Pindah peran aktif dari menu akun panel. Peran yang tidak dimiliki ditolak
 * `SwitchActiveRole` dengan 403; tujuan redirect mengikuti peran baru.
 */
class SwitchRoleController extends Controller
{
    public function __invoke(Request $request, SwitchActiveRole $switch): RedirectResponse
    {
        $data = $request->validate([
            'role' => ['required', Rule::enum(Role::class)],
        ]);

        $user = $request->user();
        $role = $switch->handle($user, $data['role']);

        return redirect()->to($user->panelHomeUrl($role));
    }
}
