<?php

namespace App\Livewire\Support;

use App\Actions\Auth\SwitchActiveRole;
use App\Enums\Role;
use App\Models\User;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Pemilih peran aktif di header panel `app` (TRD.md §14.3, DESIGN.md "Navigasi").
 * Hanya muncul untuk pengguna multi-peran. Berpindah peran tanpa reload penuh dan
 * membersihkan state peran sebelumnya lewat `SwitchActiveRole` (KP-F1-5).
 */
class RoleSwitcher extends Component
{
    #[Locked]
    public string $active = '';

    public function mount(): void
    {
        $this->active = (string) session('active_role');
    }

    public function switchTo(string $role): void
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            return;
        }

        app(SwitchActiveRole::class)->handle($user, $role);

        $this->redirect($user->panelHomeUrl($role), navigate: true);
    }

    /**
     * @return array<string, string> nilai peran => label
     */
    public function roleOptions(): array
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            return [];
        }

        $options = [];

        foreach ($user->roleValues() as $value) {
            $role = Role::tryFrom($value);

            if ($role !== null) {
                $options[$value] = $role->label();
            }
        }

        return $options;
    }

    public function render()
    {
        return view('livewire.support.role-switcher', [
            'options' => $this->roleOptions(),
        ]);
    }
}
