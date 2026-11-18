<?php

namespace App\Filament\Hubin\Resources\Companies\Pages;

use App\Actions\Company\SendSignerVerificationAction;
use App\Filament\Hubin\Resources\Companies\CompanyResource;
use App\Models\Company;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditCompany extends EditRecord
{
    protected static string $resource = CompanyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('verifikasiPembimbing')
                ->label('Verifikasi Pembimbing')
                ->icon(Heroicon::OutlinedEnvelope)
                ->color('gray')
                ->visible(fn (Company $record): bool => filled($record->pembimbing_industri_nama)
                    && filled($record->pembimbing_industri_email))
                ->requiresConfirmation()
                ->modalHeading('Kirim verifikasi email Pembimbing Industri')
                ->modalDescription(fn (Company $record): string => 'Tautan verifikasi dikirim ke '.$record->pembimbing_industri_email.'. Simpan perubahan nama/email dulu sebelum mengirim ulang.')
                ->action(function (Company $record): void {
                    app(SendSignerVerificationAction::class)->execute(
                        $record,
                        SendSignerVerificationAction::TIPE_PEMBIMBING,
                        auth()->user(),
                    );
                })
                ->successNotificationTitle('Email verifikasi Pembimbing Industri dikirim'),

            Action::make('verifikasiKepala')
                ->label('Verifikasi Kepala Industri')
                ->icon(Heroicon::OutlinedEnvelope)
                ->color('gray')
                ->visible(fn (Company $record): bool => filled($record->kepala_industri_nama)
                    && filled($record->kepala_industri_email))
                ->requiresConfirmation()
                ->modalHeading('Kirim verifikasi email Kepala Industri')
                ->modalDescription(fn (Company $record): string => 'Tautan verifikasi dikirim ke '.$record->kepala_industri_email.'. Simpan perubahan nama/email dulu sebelum mengirim ulang.')
                ->action(function (Company $record): void {
                    app(SendSignerVerificationAction::class)->execute(
                        $record,
                        SendSignerVerificationAction::TIPE_KEPALA,
                        auth()->user(),
                    );
                })
                ->successNotificationTitle('Email verifikasi Kepala Industri dikirim'),

            DeleteAction::make()
                ->visible(fn (Company $record): bool => $record->merged_into_id === null),
        ];
    }
}
