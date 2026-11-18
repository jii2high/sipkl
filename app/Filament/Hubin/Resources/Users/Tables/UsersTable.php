<?php

namespace App\Filament\Hubin\Resources\Users\Tables;

use App\Actions\User\ResetFirstLoginAction;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nis_nip')
                    ->label('NIS/NIP')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('user_type')
                    ->label('Tipe')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'siswa' => 'Siswa',
                        'guru' => 'Guru',
                        'hubin' => 'Hubin',
                        'kepsek' => 'Kepala Sekolah',
                        default => $state,
                    })
                    ->colors([
                        'info' => 'siswa',
                        'success' => 'guru',
                        'warning' => 'hubin',
                        'gray' => 'kepsek',
                    ]),

                TextColumn::make('roles.role')
                    ->label('Peran')
                    ->badge()
                    ->separator(', ')
                    ->toggleable(),

                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),

                IconColumn::make('is_first_login')
                    ->label('Login awal')
                    ->boolean()
                    ->trueIcon(Heroicon::OutlinedExclamationTriangle)
                    ->falseIcon(Heroicon::OutlinedCheckCircle)
                    ->trueColor('warning')
                    ->falseColor('success')
                    ->tooltip(fn (User $record): string => $record->is_first_login
                        ? 'Belum menyelesaikan login awal'
                        : 'Sudah menyelesaikan login awal'),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i')
                    ->timezone('Asia/Jakarta')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('user_type')
                    ->label('Tipe pengguna')
                    ->options([
                        'siswa' => 'Siswa',
                        'guru' => 'Guru',
                        'hubin' => 'Hubin',
                        'kepsek' => 'Kepala Sekolah',
                    ]),

                TernaryFilter::make('is_active')
                    ->label('Status akun')
                    ->placeholder('Semua akun')
                    ->trueLabel('Aktif')
                    ->falseLabel('Nonaktif'),

                TernaryFilter::make('is_first_login')
                    ->label('Login awal')
                    ->placeholder('Semua akun')
                    ->trueLabel('Belum selesai login awal')
                    ->falseLabel('Sudah selesai login awal'),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('resetLogin')
                    ->label('Reset login awal')
                    ->icon(Heroicon::OutlinedArrowPath)
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading(fn (User $record): string => 'Reset login awal '.$record->name)
                    ->modalDescription('Password akan dikembalikan ke NIS/NIP dan pengguna wajib melewati OTP serta ganti password pada login berikutnya.')
                    ->modalSubmitActionLabel('Reset')
                    ->action(function (User $record): void {
                        app(ResetFirstLoginAction::class)->execute($record, auth()->user());
                    })
                    ->successNotificationTitle('Alur login awal direset'),
            ])
            ->defaultSort('name');
    }
}
