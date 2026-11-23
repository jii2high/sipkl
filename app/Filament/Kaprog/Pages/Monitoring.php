<?php

namespace App\Filament\Kaprog\Pages;

use App\Enums\Role;
use App\Filament\Widgets\MonitoringGateWidget;
use App\Filament\Widgets\MonitoringTtdWidget;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

/** Dashboard Kaprog: durasi tunggu gate dan step 5-TTD (BR-F15-2). */
class Monitoring extends Page
{
    protected static ?string $navigationLabel = 'Monitoring';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static ?int $navigationSort = 4;

    public static function canAccess(): bool
    {
        return session('active_role') === Role::Kaprog->value;
    }

    public function getTitle(): string
    {
        return __('pkl.monitor.judul');
    }

    public function getSubheading(): ?string
    {
        return __('pkl.monitor.subjudul');
    }

    protected function getHeaderWidgets(): array
    {
        return [
            MonitoringGateWidget::class,
            MonitoringTtdWidget::class,
        ];
    }
}
