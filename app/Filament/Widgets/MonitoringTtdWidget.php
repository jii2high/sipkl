<?php

namespace App\Filament\Widgets;

use App\Services\MonitoringService;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

/** Durasi tunggu per step 5-TTD pada versi laporan terbaru (F15, BR-F15-2). */
class MonitoringTtdWidget extends TableWidget
{
    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading(__('pkl.monitor.ttd'))
            ->records(fn (): array => app(MonitoringService::class)->ttdSteps())
            ->paginated(false)
            ->columns([
                TextColumn::make('label')
                    ->label(__('pkl.monitor.step_kolom'))
                    ->state(fn (array $record): string => $record['step'].'. '.$record['label']),
                TextColumn::make('pending')
                    ->label(__('pkl.monitor.jumlah'))
                    ->numeric(),
                TextColumn::make('rata')
                    ->label(__('pkl.monitor.rata'))
                    ->formatStateUsing(fn (int $state): string => __('pkl.monitor.hari', ['hari' => $state])),
                TextColumn::make('terlama')
                    ->label(__('pkl.monitor.terlama'))
                    ->formatStateUsing(fn (int $state): string => __('pkl.monitor.hari', ['hari' => $state])),
            ])
            ->emptyStateHeading(__('pkl.monitor.kosong'));
    }
}
