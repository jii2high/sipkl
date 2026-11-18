<?php

namespace App\Filament\Hubin\Resources\Companies\Tables;

use App\Actions\Company\MergeCompanyAction;
use App\Models\Company;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class CompaniesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nama')
                    ->label('Nama')
                    ->searchable()
                    ->sortable()
                    ->limit(45)
                    ->tooltip(fn (Company $record): string => $record->nama),

                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('telp_fax')
                    ->label('Telepon/fax')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('website')
                    ->label('Website')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('source')
                    ->label('Sumber')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'hubin' => 'Hubin',
                        'siswa' => 'Siswa',
                        'import' => 'Impor',
                        default => $state,
                    })
                    ->colors([
                        'info' => 'hubin',
                        'warning' => 'siswa',
                        'gray' => 'import',
                    ]),

                IconColumn::make('pembimbing_industri_email_verified_at')
                    ->label('Signer PI')
                    ->boolean()
                    ->getStateUsing(fn (Company $record): bool => $record->pembimbing_industri_email_verified_at !== null)
                    ->tooltip(fn (Company $record): string => $record->pembimbing_industri_email_verified_at !== null
                        ? 'Email Pembimbing Industri sudah diverifikasi'
                        : 'Email Pembimbing Industri belum diverifikasi'),

                IconColumn::make('merged_into_id')
                    ->label('Sudah merge')
                    ->boolean()
                    ->getStateUsing(fn (Company $record): bool => $record->merged_into_id !== null)
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i')
                    ->timezone('Asia/Jakarta')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('source')
                    ->label('Sumber')
                    ->options([
                        'hubin' => 'Hubin',
                        'siswa' => 'Siswa',
                        'import' => 'Impor',
                    ]),

                TernaryFilter::make('sudah_merge')
                    ->label('Status merge')
                    ->placeholder('Semua perusahaan')
                    ->trueLabel('Sudah di-merge')
                    ->falseLabel('Belum di-merge')
                    ->queries(
                        true: fn ($query) => $query->whereNotNull('merged_into_id'),
                        false: fn ($query) => $query->whereNull('merged_into_id'),
                    ),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('merge')
                    ->label('Gabung')
                    ->icon(Heroicon::OutlinedArrowsRightLeft)
                    ->color('warning')
                    ->visible(fn (Company $record): bool => $record->merged_into_id === null)
                    ->modalHeading(fn (Company $record): string => 'Gabungkan "'.$record->nama.'"')
                    ->modalDescription('Perusahaan sumber akan ditandai sudah digabung dan seluruh referensinya dipindahkan ke perusahaan utama. Tindakan ini tercatat di audit log.')
                    ->modalSubmitActionLabel('Gabungkan')
                    ->schema([
                        Select::make('target_id')
                            ->label('Perusahaan utama')
                            ->options(fn (Company $record): array => Company::query()
                                ->belumMerge()
                                ->whereKeyNot($record->getKey())
                                ->orderBy('nama')
                                ->pluck('nama', 'id')
                                ->all())
                            ->searchable()
                            ->required(),
                        Textarea::make('alasan')
                            ->label('Alasan / catatan')
                            ->maxLength(500)
                            ->rows(2),
                    ])
                    ->action(function (Company $record, array $data): void {
                        app(MergeCompanyAction::class)->execute(
                            $record,
                            Company::query()->findOrFail($data['target_id']),
                            auth()->user(),
                            $data['alasan'] ?? null,
                        );
                    })
                    ->successNotificationTitle('Perusahaan digabungkan'),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('nama');
    }
}
