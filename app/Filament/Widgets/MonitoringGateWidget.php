<?php

namespace App\Filament\Widgets;

use App\Services\MonitoringService;
use App\Support\StatusChip;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

/** Antrean gate pengajuan: jumlah, durasi tunggu, dan peringatan kuota (F15, BR-F15-2). */
class MonitoringGateWidget extends TableWidget
{
    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading(__('pkl.monitor.gate'))
            ->description(__('pkl.monitor.catatan'))
            ->records(fn (): array => app(MonitoringService::class)->gates())
            ->paginated(false)
            ->columns([
                TextColumn::make('status')
                    ->label(__('pkl.monitor.gate_kolom'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => StatusChip::label($state))
                    ->color(fn (string $state): string => StatusChip::color($state)),
                TextColumn::make('jumlah')
                    ->label(__('pkl.monitor.jumlah'))
                    ->numeric(),
                TextColumn::make('rata')
                    ->label(__('pkl.monitor.rata'))
                    ->formatStateUsing(fn (int $state): string => __('pkl.monitor.hari', ['hari' => $state])),
                TextColumn::make('terlama')
                    ->label(__('pkl.monitor.terlama'))
                    ->formatStateUsing(fn (int $state): string => __('pkl.monitor.hari', ['hari' => $state])),
                TextColumn::make('kuota')
                    ->label(__('pkl.monitor.peringatan_kuota'))
                    ->state(fn (array $record): array => array_map(
                        fn (array $warning): string => ($warning['tone'] === 'danger'
                            ? __('pkl.monitor.kuota_penuh')
                            : __('pkl.monitor.kuota_hampir'))
                            .': '.$warning['nama'].' ('.$warning['used'].'/'.$warning['maks'].')',
                        $record['kuota'],
                    ))
                    ->listWithLineBreaks()
                    ->placeholder('-'),
            ])
            ->emptyStateHeading(__('pkl.monitor.kosong'));
    }
}
