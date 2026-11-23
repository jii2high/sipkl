<?php

namespace App\Filament\App\Pages;

use App\Enums\Role;
use App\Services\MonitoringService;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

/** Dashboard Kaprog: durasi tunggu gate dan step 5-TTD (BR-F15-2). */
class Monitoring extends Page
{
    protected string $view = 'filament.monitoring';

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

    public function gates(): array
    {
        return app(MonitoringService::class)->gates();
    }

    public function ttdSteps(): array
    {
        return app(MonitoringService::class)->ttdSteps();
    }
}
