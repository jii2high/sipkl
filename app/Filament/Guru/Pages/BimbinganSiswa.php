<?php

namespace App\Filament\Guru\Pages;

use App\Actions\Bimbingan\ApproveLaporanFinal;
use App\Actions\Bimbingan\GiveFeedback;
use App\Enums\Role;
use App\Models\BimbinganSubmission;
use App\Support\StatusChip;
use BackedEnum;
use Filament\Actions\Action;
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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

/**
 * Peninjauan bimbingan guru pembimbing (F6): feedback `diterima`/`revisi` untuk
 * tiap unggahan siswa dan ACC laporan final. Query dibatasi ke siswa yang diplot
 * ke guru ini; keputusan tetap di Action Bimbingan.
 */
class BimbinganSiswa extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationLabel = 'Bimbingan siswa';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static ?string $title = 'Bimbingan Siswa';

    protected static ?int $navigationSort = 1;

    public static function canAccess(): bool
    {
        return session('active_role') === Role::GuruPembimbing->value;
    }

    public function getSubheading(): ?string
    {
        return 'Hanya siswa bimbingan Anda yang tampil. Bagian akhir yang sudah diterima membuka ACC laporan final.';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->submissionsQuery())
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('pengajuanSiswa.siswa.name')
                    ->label('Siswa')
                    ->searchable()
                    ->wrap(),
                TextColumn::make('pengajuanSiswa.company.nama')
                    ->label('Perusahaan')
                    ->wrap()
                    ->toggleable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => StatusChip::label($state))
                    ->color(fn (string $state): string => StatusChip::color($state)),
                TextColumn::make('is_bagian_akhir')
                    ->label('Bagian')
                    ->badge()
                    ->state(fn (BimbinganSubmission $record): ?string => $record->is_bagian_akhir ? 'Bagian akhir' : null)
                    ->color('info')
                    ->placeholder('-'),
                TextColumn::make('keterangan')
                    ->label('Keterangan siswa')
                    ->placeholder('-')
                    ->wrap()
                    ->toggleable(),
                TextColumn::make('feedback_note')
                    ->label('Feedback')
                    ->placeholder('-')
                    ->wrap()
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label('Diunggah')
                    ->dateTime('d/m/Y H:i')
                    ->timezone('Asia/Jakarta'),
            ])
            ->recordActions([
                Action::make('accept')
                    ->label('Terima')
                    ->icon(Heroicon::OutlinedCheck)
                    ->color('success')
                    ->visible(fn (BimbinganSubmission $record): bool => $record->status === 'menunggu_feedback')
                    ->schema([$this->noteField()])
                    ->action(fn (BimbinganSubmission $record, array $data) => $this->run(
                        fn () => app(GiveFeedback::class)->handle($record, 'diterima', $data['note'] ?? null, auth()->user()),
                        __('pkl.bimbingan.feedback_saved'),
                    )),
                Action::make('revisi')
                    ->label('Minta revisi')
                    ->icon(Heroicon::OutlinedArrowPath)
                    ->color('warning')
                    ->visible(fn (BimbinganSubmission $record): bool => $record->status === 'menunggu_feedback')
                    ->schema([$this->noteField()])
                    ->action(fn (BimbinganSubmission $record, array $data) => $this->run(
                        fn () => app(GiveFeedback::class)->handle($record, 'revisi', $data['note'] ?? null, auth()->user()),
                        __('pkl.bimbingan.feedback_saved'),
                    )),
                Action::make('download')
                    ->label('Unduh berkas')
                    ->icon(Heroicon::OutlinedArrowDownTray)
                    ->url(fn (BimbinganSubmission $record): string => route('bimbingan.download', ['id' => $record->getKey()]))
                    ->openUrlInNewTab(),
                Action::make('approveFinal')
                    ->label('ACC laporan final')
                    ->icon(Heroicon::OutlinedClipboardDocumentCheck)
                    ->requiresConfirmation()
                    ->modalDescription('ACC membuka rantai pengesahan 5-TTD untuk siswa ini.')
                    ->visible(fn (BimbinganSubmission $record): bool => $this->canApproveFinal($record))
                    ->action(fn (BimbinganSubmission $record) => $this->run(
                        fn () => app(ApproveLaporanFinal::class)->handle(auth()->user(), $record->pengajuanSiswa, $record),
                        __('pkl.bimbingan.final_approved'),
                    )),
            ])
            ->emptyStateHeading('Belum ada bimbingan untuk ditinjau')
            ->emptyStateDescription('Unggahan bimbingan siswa bimbingan Anda muncul di sini.');
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([EmbeddedTable::make()]);
    }

    private function noteField(): Textarea
    {
        return Textarea::make('note')
            ->label('Catatan feedback (opsional)')
            ->maxLength(2000);
    }

    /** @return Builder<BimbinganSubmission> */
    private function submissionsQuery(): Builder
    {
        return BimbinganSubmission::query()
            ->where('is_archived', false)
            ->whereHas('pengajuanSiswa', fn (Builder $pengajuan): Builder => $pengajuan
                ->where('status', 'sedang_pkl')
                ->whereHas('company.plottings', fn (Builder $plot): Builder => $plot
                    ->where('guru_id', auth()->user()->getKey())
                    ->whereColumn('guru_pembimbing_plotting.academic_period_id', 'pengajuan_siswa.academic_period_id')))
            ->with(['pengajuanSiswa.siswa', 'pengajuanSiswa.company', 'pengajuanSiswa.laporanVersions']);
    }

    private function canApproveFinal(BimbinganSubmission $record): bool
    {
        return $record->is_bagian_akhir
            && $record->status === 'diterima'
            && $record->pengajuanSiswa !== null
            && $record->pengajuanSiswa->laporanVersions->isEmpty();
    }

    private function run(callable $action, string $success): void
    {
        try {
            $action();
        } catch (ValidationException $exception) {
            Notification::make()
                ->title(collect($exception->errors())->flatten()->first() ?? 'Data belum valid')
                ->danger()
                ->send();

            return;
        }

        Notification::make()->title($success)->success()->send();
    }
}
