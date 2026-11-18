<?php

namespace App\Filament\Hubin\Resources\Users\RelationManagers;

use App\Models\Jurusan;
use App\Models\Kelas;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Penugasan peran user (TASKS M2.7).
 *
 * Scope mengikuti CHECK di `user_roles`: kaprog wajib jurusan, walas wajib
 * kelas. Guru pembimbing memakai kelas sebagai scope bimbingannya.
 */
class RolesRelationManager extends RelationManager
{
    protected static string $relationship = 'roles';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('role')
                    ->label('Peran')
                    ->options([
                        'siswa' => 'Siswa',
                        'walas' => 'Wali kelas',
                        'guru_pembimbing' => 'Guru pembimbing',
                        'kaprog' => 'Kepala program (Kaprog)',
                        'hubin' => 'Hubin',
                        'kepsek' => 'Kepala sekolah',
                    ])
                    ->required()
                    ->live()
                    ->afterStateUpdated(function (Set $set, ?string $state): void {
                        if ($state !== 'kaprog') {
                            $set('jurusan_id', null);
                        }

                        if (! in_array($state, ['walas', 'guru_pembimbing'], true)) {
                            $set('kelas_id', null);
                        }
                    }),

                Select::make('jurusan_id')
                    ->label('Jurusan')
                    ->options(fn (): array => Jurusan::query()->orderBy('nama')->pluck('nama', 'id')->all())
                    ->searchable()
                    ->visible(fn (Get $get): bool => $get('role') === 'kaprog')
                    ->required(fn (Get $get): bool => $get('role') === 'kaprog'),

                Select::make('kelas_id')
                    ->label('Kelas')
                    ->options(fn (): array => Kelas::query()->orderBy('nama')->pluck('nama', 'id')->all())
                    ->searchable()
                    ->visible(fn (Get $get): bool => in_array($get('role'), ['walas', 'guru_pembimbing'], true))
                    ->required(fn (Get $get): bool => $get('role') === 'walas'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('role')
            ->columns([
                TextColumn::make('role')
                    ->label('Peran')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'siswa' => 'Siswa',
                        'walas' => 'Wali kelas',
                        'guru_pembimbing' => 'Guru pembimbing',
                        'kaprog' => 'Kepala program',
                        'hubin' => 'Hubin',
                        'kepsek' => 'Kepala sekolah',
                        default => $state,
                    }),

                TextColumn::make('jurusan.nama')
                    ->label('Jurusan')
                    ->placeholder('—'),

                TextColumn::make('kelas.nama')
                    ->label('Kelas')
                    ->placeholder('—'),

                TextColumn::make('created_at')
                    ->label('Ditambahkan')
                    ->dateTime('d M Y H:i')
                    ->timezone('Asia/Jakarta')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
