<?php

namespace App\Filament\Kaprog\Pages;

use App\Actions\Sidang\ApproveUnlock;
use App\Actions\Sidang\SetKelulusan;
use App\Enums\Role;
use App\Models\FormDefinition;
use App\Models\FormSubmission;
use App\Models\NilaiSidangRevision;
use App\Models\SidangResultsSummary;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
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
 * Tindak lanjut nilai sidang Kaprog (F9, Q-I): menyetujui permintaan revisi penguji
 * dan menetapkan status kelulusan manual. Keputusan tetap di Action Sidang.
 */
class PenilaianSidang extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationLabel = 'Nilai sidang';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAcademicCap;

    protected static ?string $title = 'Nilai Sidang';

    protected static ?int $navigationSort = 8;

    public static function canAccess(): bool
    {
        return session('active_role') === Role::Kaprog->value;
    }

    public function getSubheading(): ?string
    {
        return 'Setujui permintaan revisi penguji, lalu tetapkan kelulusan setiap siswa.';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                FormSubmission::query()
                    ->with(['pengajuanSiswa.siswa', 'jadwalSidang.externalExaminer'])
                    ->where('form_type', FormDefinition::TYPE_SIDANG)
                    ->where('is_submitted', true)
                    ->whereHas('pengajuanSiswa.siswa', fn ($student) => $student
                        ->where('jurusan_id', auth()->user()->roleScopeId(Role::Kaprog)))
            )
            ->defaultSort('submitted_at', 'desc')
            ->columns([
                TextColumn::make('pengajuanSiswa.siswa.name')
                    ->label('Siswa')
                    ->searchable()
                    ->wrap(),
                TextColumn::make('jadwalSidang.externalExaminer.nama')
                    ->label('Penguji')
                    ->placeholder('-'),
                TextColumn::make('jadwalSidang.tanggal')
                    ->label('Tanggal sidang')
                    ->date('d/m/Y')
                    ->placeholder('-'),
                TextColumn::make('nilai_total')
                    ->label('Nilai total')
                    ->numeric()
                    ->placeholder('-'),
                TextColumn::make('revisi')
                    ->label('Permintaan revisi')
                    ->state(fn (FormSubmission $record): ?string => $this->pendingRevision($record) === null
                        ? null
                        : 'Menunggu persetujuan')
                    ->badge()
                    ->color('warning')
                    ->placeholder('-'),
                TextColumn::make('alasan_revisi')
                    ->label('Alasan revisi')
                    ->state(fn (FormSubmission $record): ?string => $this->pendingRevision($record)?->reason)
                    ->placeholder('-')
                    ->wrap(),
                TextColumn::make('kelulusan')
                    ->label('Kelulusan')
                    ->state(fn (FormSubmission $record): ?string => $this->summary($record)?->status_kelulusan)
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        SetKelulusan::LULUS => 'success',
                        SetKelulusan::TIDAK_LULUS => 'danger',
                        default => 'gray',
                    })
                    ->placeholder('Belum ditetapkan'),
            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('approveUnlock')
                        ->label('Setujui revisi')
                        ->icon(Heroicon::OutlinedLockOpen)
                        ->visible(fn (FormSubmission $record): bool => $this->pendingRevision($record) !== null)
                        ->requiresConfirmation()
                        ->modalDescription('Persetujuan menerbitkan satu tautan + PIN baru untuk penguji, berlaku 24 jam.')
                        ->action(function (FormSubmission $record): void {
                            $revision = $this->pendingRevision($record);

                            if ($revision === null) {
                                return;
                            }

                            try {
                                $result = app(ApproveUnlock::class)->handle($revision, auth()->user());
                            } catch (ValidationException $exception) {
                                $this->notifyValidation($exception);

                                return;
                            }

                            Notification::make()
                                ->title(__('pkl.sidang.unlock_approved'))
                                ->body('PIN: '.$result['pin'])
                                ->persistent()
                                ->success()
                                ->actions(filled($result['wa_url'])
                                    ? [Action::make('wa')->label('Kirim WhatsApp')->button()->url($result['wa_url'], shouldOpenInNewTab: true)]
                                    : [])
                                ->send();
                        }),
                    Action::make('setKelulusan')
                        ->label('Tetapkan kelulusan')
                        ->icon(Heroicon::OutlinedAcademicCap)
                        ->fillForm(fn (FormSubmission $record): array => [
                            'status' => $this->summary($record)?->status_kelulusan,
                            'catatan' => $this->summary($record)?->catatan_kelulusan,
                        ])
                        ->schema([
                            Select::make('status')
                                ->label('Status kelulusan')
                                ->options([
                                    SetKelulusan::LULUS => 'Lulus',
                                    SetKelulusan::TIDAK_LULUS => 'Tidak Lulus',
                                ])
                                ->required(),
                            Textarea::make('catatan')
                                ->label('Catatan (opsional)')
                                ->maxLength(2000),
                        ])
                        ->action(function (FormSubmission $record, array $data): void {
                            try {
                                app(SetKelulusan::class)->handle(
                                    $record,
                                    $data['status'],
                                    $data['catatan'] ?? null,
                                    auth()->user(),
                                );
                            } catch (ValidationException $exception) {
                                $this->notifyValidation($exception);

                                return;
                            }

                            Notification::make()->title(__('pkl.sidang.kelulusan_saved'))->success()->send();
                        }),
                ]),
            ])
            ->emptyStateHeading('Belum ada nilai sidang yang dikunci')
            ->emptyStateDescription('Nilai muncul setelah penguji mengunci penilaian.');
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([EmbeddedTable::make()]);
    }

    private function pendingRevision(FormSubmission $submission): ?NilaiSidangRevision
    {
        return $submission->pendingRevision();
    }

    private function summary(FormSubmission $submission): ?SidangResultsSummary
    {
        return SidangResultsSummary::query()
            ->where('form_submission_id', $submission->getKey())
            ->first();
    }

    private function notifyValidation(ValidationException $exception): void
    {
        Notification::make()
            ->title(collect($exception->errors())->flatten()->first() ?? 'Data nilai belum valid')
            ->danger()
            ->send();
    }
}
