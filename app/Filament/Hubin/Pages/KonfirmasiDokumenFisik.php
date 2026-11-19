<?php

namespace App\Filament\Hubin\Pages;

use App\Actions\Pengajuan\ConfirmPhysicalDocument;
use App\Enums\PengajuanStatus;
use App\Models\PengajuanSiswa;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

/**
 * Daftar siswa PKL provisional (T4.2, F3 alur 7). Hubin hanya menandai dokumen
 * fisik sudah diterima; keputusan penerimaan Kaprog tidak berubah.
 */
class KonfirmasiDokumenFisik extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament.hubin.pages.konfirmasi-dokumen-fisik';

    protected static ?string $navigationLabel = 'Dokumen fisik';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentCheck;

    protected static ?int $navigationSort = 2;

    public static function canAccess(): bool
    {
        return session('active_role') === 'hubin';
    }

    public function getTitle(): string
    {
        return 'Konfirmasi dokumen fisik';
    }

    public function getSubheading(): ?string
    {
        return 'Siswa berstatus provisional sampai dokumen fisik PKL diterima Hubin.';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                PengajuanSiswa::query()
                    ->where('status', PengajuanStatus::SedangPkl->value)
                    ->where('is_provisional', true)
                    ->with(['siswa', 'company', 'academicPeriod'])
            )
            ->defaultSort('accepted_at')
            ->columns([
                TextColumn::make('siswa.name')
                    ->label('Siswa')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('company.nama')
                    ->label('Perusahaan')
                    ->searchable()
                    ->wrap(),
                TextColumn::make('academicPeriod.tahun_ajaran')
                    ->label('Periode')
                    ->toggleable(),
                TextColumn::make('accepted_at')
                    ->label('Diterima')
                    ->dateTime('d M Y H:i')
                    ->timezone(config('pkl.timezone.operational'))
                    ->sortable(),
            ])
            ->recordActions([
                Action::make('confirm')
                    ->label('Dokumen diterima')
                    ->icon(Heroicon::OutlinedCheckBadge)
                    ->requiresConfirmation()
                    ->visible(fn (PengajuanSiswa $record): bool => auth()->user()?->can(
                        'confirmPhysicalDocument',
                        $record,
                    ) ?? false)
                    ->action(function (PengajuanSiswa $record): void {
                        app(ConfirmPhysicalDocument::class)->handle($record, auth()->user());

                        Notification::make()
                            ->title(__('pkl.pengajuan.physical_confirmed'))
                            ->success()
                            ->send();
                    }),
            ])
            ->emptyStateHeading('Tidak ada dokumen fisik tertunda')
            ->emptyStateDescription('Siswa provisional akan muncul di sini setelah diterima Kaprog.');
    }
}
