<?php

namespace App\Filament\Hubin\Resources\AcademicPeriods\Tables;

use App\Models\AcademicPeriod;
use App\Services\AcademicPeriodGuard;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class AcademicPeriodsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('tahun_ajaran')
                    ->label('Tahun ajaran')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('batch')
                    ->label('Batch')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => 'Batch '.$state),

                TextColumn::make('tanggal_mulai')
                    ->label('Mulai')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('tanggal_selesai')
                    ->label('Selesai')
                    ->date('d M Y')
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),

                IconColumn::make('locked_for_new_submission')
                    ->label('Terkunci')
                    ->boolean(),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i')
                    ->timezone('Asia/Jakarta')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('batch')
                    ->label('Batch')
                    ->options([
                        '1' => 'Batch 1',
                        '2' => 'Batch 2',
                    ]),

                TernaryFilter::make('is_active')
                    ->label('Status aktif')
                    ->placeholder('Semua periode')
                    ->trueLabel('Aktif')
                    ->falseLabel('Tidak aktif'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->disabled(fn (AcademicPeriod $record): bool => app(AcademicPeriodGuard::class)->punyaDataTerkait($record))
                    ->tooltip(fn (AcademicPeriod $record): ?string => app(AcademicPeriodGuard::class)->punyaDataTerkait($record)
                        ? 'Periode sudah punya data terkait sehingga tidak bisa dihapus'
                        : null),
            ])
            ->defaultSort('tahun_ajaran', 'desc');
    }
}
