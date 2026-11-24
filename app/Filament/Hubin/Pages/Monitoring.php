<?php

namespace App\Filament\Hubin\Pages;

use App\Filament\Widgets\MonitoringGateWidget;
use App\Filament\Widgets\MonitoringTtdWidget;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

/** Dashboard Hubin: durasi tunggu gate dan step 5-TTD (BR-F15-2). */
class Monitoring extends Page
{
    protected static ?string $navigationLabel = 'Monitoring';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static ?int $navigationSort = 4;

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
