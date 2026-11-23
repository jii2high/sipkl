<?php

namespace App\Providers\Filament;

use App\Providers\Filament\Concerns\ConfiguresPanel;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\View\PanelsRenderHook;
use Filament\Widgets\AccountWidget;
use Illuminate\Contracts\View\View;

class HubinPanelProvider extends PanelProvider
{
    use ConfiguresPanel;

    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('hubin')
            ->path('hubin')
            ->brandName('SIM PKL Hubin')
            ->colors($this->panelColors())
            ->darkMode(true)
            // Token dan aturan sentuh Design.md juga berlaku di panel Filament.
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn (): View => view('filament.panel-assets'),
            )
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
            ->middleware($this->panelMiddleware())
            ->authMiddleware($this->panelAuthMiddleware(), isPersistent: true);
    }
}
