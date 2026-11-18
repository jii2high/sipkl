<?php

namespace App\Filament\Hubin\Concerns;

use App\Actions\Master\ImportData;
use App\Support\ImportSheet;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;

/**
 * Aksi unggah berkas impor massal untuk resource Hubin (T2.1/T2.2). Berkas dibaca
 * jadi baris oleh `ImportSheet`, lalu setiap baris diproses `ImportData` dalam
 * transaksinya sendiri; hasilnya dilaporkan sebagai notifikasi.
 */
trait HasImportAction
{
    /**
     * @param  string  $kind  Salah satu `ImportData::KIND_*`.
     * @param  class-string  $subject  Model untuk pemeriksaan Policy `import`.
     */
    public static function importAction(string $kind, string $subject): Action
    {
        return Action::make('import')->label('Impor')
            ->icon(Heroicon::OutlinedArrowUpTray)
            ->visible(fn (): bool => auth()->user()?->can('import', $subject) === true)
            ->modalDescription(__('pkl.import.hint'))
            ->schema([
                FileUpload::make('file')
                    ->label('Berkas (CSV/XLSX)')
                    ->disk('local')
                    ->storeFiles(false)
                    ->acceptedFileTypes([
                        'text/csv',
                        'application/csv',
                        'application/vnd.ms-excel',
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    ])
                    ->maxSize(10 * 1024)
                    ->required(),
            ])
            ->action(function (array $data) use ($kind): void {
                $report = app(ImportData::class)->handle(
                    $kind,
                    ImportSheet::rows($data['file']),
                    auth()->user(),
                );

                $notification = Notification::make()->title($report->summary());

                if ($report->hasFailures()) {
                    $notification->warning()->body(implode("\n", $report->failureLines()));
                } else {
                    $notification->success();
                }

                $notification->send();
            });
    }
}
