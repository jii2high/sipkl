<?php

namespace App\Filament\Hubin\Pages;

use App\Services\MonitoringService;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

/** Dashboard Hubin: durasi tunggu gate dan step 5-TTD (BR-F15-2). */
class Monitoring extends Page
{
    protected string $view = 'filament.monitoring';

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

    public function gates(): array
    {
        return app(MonitoringService::class)->gates();
    }

    public function ttdSteps(): array
    {
        return app(MonitoringService::class)->ttdSteps();
    }
}
