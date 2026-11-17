<?php

namespace App\Livewire\App;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class RoleSwitcher extends Component
{
    public function switch(string $role): void
    {
        $user = Auth::user();

        abort_unless($user && $user->roles()->where('role', $role)->exists(), 403);

        session(['active_role' => $role]);

        $this->redirect($role === 'hubin' ? '/hubin' : '/app');
    }

    public function render(): View
    {
        return view('livewire.app.role-switcher', [
            'roles' => Auth::user()?->roles()->pluck('role')->all() ?? [],
        ]);
    }
}
