<?php

namespace App\Filament\Kaprog\Pages;

use App\Actions\Pengajuan\AcceptByCompany;
use App\Actions\Pengajuan\ApproveByKaprog;
use App\Actions\Pengajuan\ApproveWithdrawal;
use App\Actions\Pengajuan\RejectByCompany;
use App\Actions\Pengajuan\RejectByKaprog;
use App\Enums\PengajuanStatus;
use App\Enums\Role;
use App\Exceptions\QuotaExceeded;
use App\Models\PengajuanSiswa;
use App\Services\QuotaService;
use App\Support\StatusChip;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Validation\ValidationException;

/**
 * Antrean Gate 1 (Kaprog): verifikasi pengajuan, keputusan setelah balasan
 * perusahaan, dan penarikan PKL. Data di luar scope jurusan tidak pernah dimuat
 * (TRD.md §7.3).
 */
class AntreanPengajuan extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationLabel = 'Antrean pengajuan';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInboxArrowDown;

    protected static ?int $navigationSort = 1;

    public static function canAccess(): bool
    {
        return session('active_role') === Role::Kaprog->value;
    }

    public function getTitle(): string
    {
        return 'Antrean verifikasi Kaprog';
    }

    public function getSubheading(): ?string
    {
        return 'Verifikasi pengajuan, keputusan setelah balasan perusahaan, dan penarikan PKL.';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                PengajuanSiswa::query()
                    ->visibleTo(auth()->user(), Role::Kaprog->value)
                    ->with(['siswa.kelas', 'company', 'academicPeriod'])
                    ->where(function ($query): void {
                        $query->whereIn('status', [
                            PengajuanStatus::MenungguVerifikasiKaprog->value,
                            PengajuanStatus::MenungguBalasanPerusahaan->value,
                        ])->orWhere(function ($query): void {
                            $query->where('status', PengajuanStatus::SedangPkl->value)
                                ->whereNotNull('withdraw_requested_at');
                        });
                    })
            )
            ->defaultSort('updated_at')
            ->poll('30s')
            ->columns([
                TextColumn::make('siswa.name')
                    ->label('Siswa')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('siswa.kelas.nama')
                    ->label('Kelas')
                    ->toggleable(),
                TextColumn::make('company.nama')
                    ->label('Perusahaan')
                    ->searchable()
                    ->wrap(),
                TextColumn::make('academicPeriod.tahun_ajaran')
                    ->label('Periode')
                    ->toggleable(),
                TextColumn::make('kuota')
                    ->label('Kuota perusahaan')
                    ->badge()
                    ->state(fn (PengajuanSiswa $record): string => app(QuotaService::class)->label(
                        app(QuotaService::class)->used((int) $record->company_id, (int) $record->academic_period_id),
                    ))
                    ->color(function (PengajuanSiswa $record): string {
                        $quota = app(QuotaService::class);

                        return match ($quota->tone($quota->used((int) $record->company_id, (int) $record->academic_period_id))) {
                            'danger' => 'danger',
                            'warning' => 'warning',
                            default => 'gray',
                        };
                    }),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => StatusChip::label($state))
                    ->color(fn (string $state): string => StatusChip::color($state)),
                TextColumn::make('updated_at')
                    ->label('Diperbarui')
                    ->since()
                    ->sortable(),
            ])
            ->recordActions([
                Action::make('approveKaprog')
                    ->label('Setujui')
                    ->icon(Heroicon::OutlinedCheck)
                    ->requiresConfirmation()
                    ->visible(fn (PengajuanSiswa $record): bool => auth()->user()?->can('approveKaprog', $record) ?? false)
                    ->action(function (PengajuanSiswa $record): void {
                        app(ApproveByKaprog::class)->handle($record, auth()->user());

                        Notification::make()->title(__('pkl.pengajuan.approved_kaprog'))->success()->send();
                    }),
                Action::make('acceptCompany')
                    ->label('Terima siswa')
                    ->icon(Heroicon::OutlinedCheckBadge)
                    ->visible(fn (PengajuanSiswa $record): bool => auth()->user()?->can('acceptByCompany', $record) ?? false)
                    ->schema([
                        DatePicker::make('tanggal_mulai')
                            ->label('Tanggal mulai PKL')
                            ->default(fn (PengajuanSiswa $record) => $record->academicPeriod?->tanggal_mulai)
                            ->required(),
                        DatePicker::make('tanggal_selesai')
                            ->label('Tanggal selesai PKL')
                            ->default(fn (PengajuanSiswa $record) => $record->academicPeriod?->tanggal_selesai)
                            ->required(),
                        Toggle::make('has_physical_document')
                            ->label('Dokumen fisik sudah diterima')
                            ->helperText('Bila belum, siswa berstatus provisional sampai Hubin mengonfirmasi.'),
                    ])
                    ->action(function (PengajuanSiswa $record, array $data): void {
                        try {
                            app(AcceptByCompany::class)->handle($record, auth()->user(), $data);
                        } catch (QuotaExceeded|ValidationException $exception) {
                            $message = $exception instanceof ValidationException
                                ? (collect($exception->errors())->flatten()->first() ?? $exception->getMessage())
                                : $exception->getMessage();

                            Notification::make()
                                ->title('Siswa belum dapat diterima')
                                ->body($message)
                                ->danger()
                                ->send();

                            return;
                        }

                        Notification::make()->title(__('pkl.pengajuan.accepted_company'))->success()->send();
                    }),
                Action::make('reject')
                    ->label('Tolak')
                    ->color('danger')
                    ->icon(Heroicon::OutlinedXMark)
                    ->visible(fn (PengajuanSiswa $record): bool => (auth()->user()?->can('rejectKaprog', $record) ?? false)
                        || (auth()->user()?->can('rejectByCompany', $record) ?? false))
                    ->schema([
                        Textarea::make('catatan')
                            ->label('Catatan penolakan')
                            ->helperText('Minimal :min karakter agar siswa tahu yang harus diperbaiki.', ['min' => (int) config('pkl.rejection_note.min_length')])
                            ->required()
                            ->minLength((int) config('pkl.rejection_note.min_length'))
                            ->validationMessages([
                                'min' => __('pkl.rejection_note.min', ['min' => (int) config('pkl.rejection_note.min_length')]),
                            ]),
                    ])
                    ->action(function (PengajuanSiswa $record, array $data): void {
                        if ($record->status === PengajuanStatus::MenungguBalasanPerusahaan->value) {
                            app(RejectByCompany::class)->handle($record, auth()->user(), $data['catatan']);

                            Notification::make()->title(__('pkl.pengajuan.rejected_company'))->success()->send();

                            return;
                        }

                        app(RejectByKaprog::class)->handle($record, auth()->user(), $data['catatan']);

                        Notification::make()->title(__('pkl.pengajuan.rejected_kaprog'))->success()->send();
                    }),
                Action::make('approveWithdrawal')
                    ->label('Setujui penarikan')
                    ->color('danger')
                    ->icon(Heroicon::OutlinedArrowLeftOnRectangle)
                    ->requiresConfirmation()
                    ->visible(fn (PengajuanSiswa $record): bool => auth()->user()?->can('approveWithdrawal', $record) ?? false)
                    ->action(function (PengajuanSiswa $record): void {
                        app(ApproveWithdrawal::class)->handle($record, auth()->user());

                        Notification::make()->title(__('pkl.pengajuan.withdraw_approved'))->success()->send();
                    }),
            ])
            ->emptyStateHeading('Tidak ada pengajuan menunggu')
            ->emptyStateDescription('Antrean akan muncul otomatis saat ada pengajuan baru.');
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([EmbeddedTable::make()]);
    }
}
