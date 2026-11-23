<?php

namespace App\Providers\Filament\Concerns;

use App\Enums\Role;
use App\Http\Middleware\EnsureActiveRole;
use App\Http\Middleware\PanelAuthenticate;
use App\Http\Middleware\PanelAuthenticateSession;
use Filament\Actions\Action;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

/**
 * Konfigurasi yang identik di semua panel Filament: warna token DESIGN.md,
 * middleware dasar, dan middleware autentikasi. Panel tetap menentukan id,
 * path, merek, dan direktori discovery sendiri.
 */
trait ConfiguresPanel
{
    /**
     * Warna token DESIGN.md; Filament membangkitkan palet terang/gelap dari hex.
     *
     * @return array<string, string>
     */
    protected function panelColors(): array
    {
        return [
            'primary' => '#1E40AF',
            'info' => '#06B6D4',
            'success' => '#166534',
            'warning' => '#92400E',
            'danger' => '#991B1B',
        ];
    }

    /**
     * @return array<int, class-string>
     */
    protected function panelMiddleware(): array
    {
        return [
            EncryptCookies::class,
            AddQueuedCookiesToResponse::class,
            StartSession::class,
            PanelAuthenticateSession::class,
            ShareErrorsFromSession::class,
            PreventRequestForgery::class,
            SubstituteBindings::class,
            DisableBladeIconComponents::class,
            DispatchServingFilamentEvent::class,
        ];
    }

    /**
     * Item menu akun untuk berpindah peran. Peran yang sedang aktif dan peran
     * yang tidak dimiliki tidak ditampilkan. POST dipakai agar perpindahan peran
     * tidak terjadi lewat tautan GET.
     *
     * @return array<int, Action>
     */
    protected function roleMenuItems(): array
    {
        return array_map(
            fn (Role $role): Action => Action::make('switch-'.$role->value)
                ->label('Masuk sebagai '.$role->label())
                ->icon('heroicon-o-arrows-right-left')
                ->url(fn (): string => route('role.switch', ['role' => $role->value]))
                ->postToUrl()
                ->visible(fn (): bool => auth()->user()?->hasRole($role) === true
                    && session('active_role') !== $role->value),
            Role::cases(),
        );
    }

    /**
     * Middleware ini persisten agar `EnsureActiveRole` juga berjalan pada setiap
     * request Livewire, bukan hanya muat halaman pertama.
     *
     * @return array<int, class-string>
     */
    protected function panelAuthMiddleware(): array
    {
        return [
            PanelAuthenticate::class,
            EnsureActiveRole::class,
        ];
    }
}
