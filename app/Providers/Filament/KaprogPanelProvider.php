<?php

namespace App\Providers\Filament;

use App\Providers\Filament\Concerns\ConfiguresPanel;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\View\PanelsRenderHook;
use Filament\Widgets\AccountWidget;
use Illuminate\Contracts\View\View;

class KaprogPanelProvider extends PanelProvider
{
    use ConfiguresPanel;

    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('kaprog')
            ->path('kaprog')
            ->brandName('SIM PKL Kaprog')
            ->colors($this->panelColors())
            ->darkMode(true)
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn (): View => view('filament.panel-assets'),
            )
            ->renderHook(
                PanelsRenderHook::TOPBAR_END,
                fn (): View => view('filament.theme-switcher'),
            )
            ->discoverResources(in: app_path('Filament/Kaprog/Resources'), for: 'App\Filament\Kaprog\Resources')
            ->discoverPages(in: app_path('Filament/Kaprog/Pages'), for: 'App\Filament\Kaprog\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Kaprog/Widgets'), for: 'App\Filament\Kaprog\Widgets')
            ->widgets([
                AccountWidget::class,
            ])
            ->middleware($this->panelMiddleware())
            ->authMiddleware($this->panelAuthMiddleware(), isPersistent: true);
    }
}
