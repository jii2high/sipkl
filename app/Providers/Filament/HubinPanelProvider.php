<?php

namespace App\Providers\Filament;

use App\Http\Middleware\EnsureActiveRole;
use App\Http\Middleware\PanelAuthenticate;
use App\Http\Middleware\PanelAuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Filament\Widgets\AccountWidget;
use Illuminate\Contracts\View\View;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class HubinPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('hubin')
            ->path('hubin')
            ->brandName('SIM PKL Hubin')
            ->colors([
                'primary' => Color::Blue,
                'info' => Color::Sky,
                'success' => Color::Green,
                'warning' => Color::Amber,
                'danger' => Color::Red,
            ])
            ->darkMode(true)
            // Login ditangani halaman Livewire kustom `/login` (T1.1).
            ->renderHook(
                PanelsRenderHook::TOPBAR_END,
                fn (): View => view('filament.theme-switcher'),
            )
            ->discoverResources(in: app_path('Filament/Hubin/Resources'), for: 'App\Filament\Hubin\Resources')
            ->discoverPages(in: app_path('Filament/Hubin/Pages'), for: 'App\Filament\Hubin\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Hubin/Widgets'), for: 'App\Filament\Hubin\Widgets')
            ->widgets([
                AccountWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                PanelAuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                PanelAuthenticate::class,
                EnsureActiveRole::class,
            ]);
    }
}
