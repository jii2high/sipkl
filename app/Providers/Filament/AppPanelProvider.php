<?php

namespace App\Providers\Filament;

use App\Providers\Filament\Concerns\ConfiguresPanel;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\View\PanelsRenderHook;
use Filament\Widgets\AccountWidget;
use Illuminate\Contracts\View\View;

class AppPanelProvider extends PanelProvider
{
    use ConfiguresPanel;

    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('app')
            ->path('app')
            // Panel utama (portal siswa/guru); `hubin` khusus peran Hubin.
            ->default()
            ->brandName('SIM PKL Siswa & Guru')
            ->colors($this->panelColors())
            ->darkMode(true)
            ->userMenuItems($this->roleMenuItems())
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
            ->discoverResources(in: app_path('Filament/App/Resources'), for: 'App\Filament\App\Resources')
            ->discoverPages(in: app_path('Filament/App/Pages'), for: 'App\Filament\App\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/App/Widgets'), for: 'App\Filament\App\Widgets')
            ->widgets([
                AccountWidget::class,
            ])
            ->middleware($this->panelMiddleware())
            ->authMiddleware($this->panelAuthMiddleware(), isPersistent: true);
    }
}
