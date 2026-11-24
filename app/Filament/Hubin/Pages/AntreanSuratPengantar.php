<?php

namespace App\Filament\Hubin\Pages;

use App\Actions\Pengajuan\IssueSuratPengantar;
use App\Exceptions\DocumentIssuanceHeld;
use App\Models\PengajuanPkl;
use App\Services\QuotaService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

/**
 * Antrean penerbitan surat pengantar per company group (T4.2, F3 alur 5).
 * Nomor, PDF, dan QR dibuat `IssueSuratPengantar`; halaman ini hanya memilih
 * group dan menampilkan sisa kuota.
 */
class AntreanSuratPengantar extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationLabel = 'Surat pengantar';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelope;

    protected static ?int $navigationSort = 1;

    public static function canAccess(): bool
    {
        return session('active_role') === 'hubin';
    }

    public function getTitle(): string
    {
        return 'Antrean surat pengantar';
    }

    public function getSubheading(): ?string
    {
        return 'Group perusahaan yang sudah ditandatangani Walas dan menunggu nomor surat.';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                PengajuanPkl::query()
                    ->where('status', 'terbentuk')
                    ->with(['company', 'academicPeriod'])
                    ->withCount('students')
            )
            ->defaultSort('updated_at')
            ->columns([
                TextColumn::make('company.nama')
                    ->label('Perusahaan')
                    ->searchable()
                    ->wrap(),
                TextColumn::make('academicPeriod.tahun_ajaran')
                    ->label('Periode')
                    ->toggleable(),
                TextColumn::make('students_count')
                    ->label('Siswa')
                    ->badge(),
                TextColumn::make('kuota')
                    ->label('Kuota')
                    ->state(fn (PengajuanPkl $record): string => app(QuotaService::class)->label(
                        app(QuotaService::class)->used((int) $record->company_id, (int) $record->academic_period_id),
                    ))
                    ->color(function (PengajuanPkl $record): string {
                        $quota = app(QuotaService::class);

                        return match ($quota->tone($quota->used((int) $record->company_id, (int) $record->academic_period_id))) {
                            'danger' => 'danger',
                            'warning' => 'warning',
                            default => 'gray',
                        };
                    }),
                TextColumn::make('updated_at')
                    ->label('Diperbarui')
                    ->since()
                    ->sortable(),
            ])
            ->recordActions([
                Action::make('issue')
                    ->label('Terbitkan surat')
                    ->icon(Heroicon::OutlinedPaperAirplane)
                    ->requiresConfirmation()
                    ->visible(fn (PengajuanPkl $record): bool => auth()->user()?->can('issueLetter', $record) ?? false)
                    ->action(function (PengajuanPkl $record): void {
                        try {
                            app(IssueSuratPengantar::class)->handle($record, auth()->user());
                        } catch (DocumentIssuanceHeld $exception) {
                            Notification::make()
                                ->title('Penerbitan ditahan')
                                ->body($exception->getMessage())
                                ->danger()
                                ->send();

                            return;
                        }

                        Notification::make()
                            ->title(__('pkl.pengajuan.letter_issued'))
                            ->success()
                            ->send();
                    }),
            ])
            ->emptyStateHeading('Tidak ada group menunggu surat')
            ->emptyStateDescription('Group muncul setelah Walas menandatangani pengajuan siswa.');
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([EmbeddedTable::make()]);
    }
}
