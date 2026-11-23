<?php

namespace App\Filament\Kaprog\Pages;

use App\Actions\Pengesahan\BulkApproveSignatures;
use App\Actions\Pengesahan\IssueSignerLink;
use App\Actions\Pengesahan\PengesahanAction;
use App\Actions\Pengesahan\ReissueSignerLink;
use App\Actions\Pengesahan\RejectStep;
use App\Actions\Pengesahan\SignStep;
use App\Enums\Role;
use App\Exceptions\DocumentIssuanceHeld;
use App\Models\DocumentSignature;
use App\Models\MagicLink;
use App\Services\MagicLinkService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
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
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/**
 * Pengesahan laporan bagian Kaprog (F7): step 2 ditandatangani Kaprog, step 3/4
 * ditandatangani signer industri lewat tautan yang diterbitkan di sini. Keputusan
 * tetap di Action Pengesahan; bulk melaporkan hasil per item.
 */
class PengesahanLaporan extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationLabel = 'Pengesahan laporan';

    protected static ?string $title = 'Pengesahan Laporan 5-TTD';

    protected static ?int $navigationSort = 5;

    public static function canAccess(): bool
    {
        return session('active_role') === Role::Kaprog->value;
    }

    public function getSubheading(): ?string
    {
        return 'Tanda tangan step 2 milik Kaprog. Step 3 dan 4 ditandatangani signer industri lewat tautan yang Anda terbitkan.';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->signaturesQuery())
            ->defaultSort('step_order')
            ->columns([
                TextColumn::make('pengajuanSiswa.siswa.name')
                    ->label('Siswa')
                    ->searchable()
                    ->wrap(),
                TextColumn::make('pengajuanSiswa.company.nama')
                    ->label('Perusahaan')
                    ->wrap()
                    ->toggleable(),
                TextColumn::make('step_order')
                    ->label('Step')
                    ->badge()
                    ->formatStateUsing(fn (int $state): string => $state.'. '.(PengesahanAction::STEP_LABELS[$state] ?? '')),
                TextColumn::make('laporan_version_id')
                    ->label('Laporan')
                    ->state(fn (DocumentSignature $record): string => $record->laporanVersion === null
                        ? '-'
                        : 'Versi '.$record->laporanVersion->version_number)
                    ->url(fn (DocumentSignature $record): ?string => $record->laporanVersion === null
                        ? null
                        : route('laporan.download', ['version' => $record->laporanVersion->getKey()]))
                    ->openUrlInNewTab(),
                TextColumn::make('rejection_note')
                    ->label('Alasan ditolak')
                    ->placeholder('-')
                    ->wrap()
                    ->toggleable(),
            ])
            ->recordActions([
                Action::make('approve')
                    ->label('Setujui (TTD step 2)')
                    ->icon(Heroicon::OutlinedCheck)
                    ->visible(fn (DocumentSignature $record): bool => (int) $record->step_order === 2)
                    ->requiresConfirmation()
                    ->modalDescription('Tanda tangan step 2 tersimpan atas nama Anda beserta jejak auditnya.')
                    ->action(fn (DocumentSignature $record) => $this->run(
                        fn () => app(SignStep::class)->handle($record, auth()->user()),
                        __('pkl.pengesahan.signed', ['step' => $record->step_order]),
                    )),
                Action::make('reject')
                    ->label('Tolak')
                    ->icon(Heroicon::OutlinedXMark)
                    ->color('danger')
                    ->visible(fn (DocumentSignature $record): bool => (int) $record->step_order === 2)
                    ->schema([
                        Textarea::make('catatan')
                            ->label('Catatan penolakan')
                            ->helperText('Minimal 10 karakter.')
                            ->required()
                            ->minLength(10)
                            ->maxLength(2000),
                    ])
                    ->action(fn (DocumentSignature $record, array $data) => $this->run(
                        fn () => app(RejectStep::class)->handle($record, (string) $data['catatan'], auth()->user()),
                        __('pkl.pengesahan.rejected'),
                    )),
                ActionGroup::make([
                    Action::make('issue')
                        ->label('Terbitkan tautan')
                        ->icon(Heroicon::OutlinedPaperAirplane)
                        ->visible(fn (DocumentSignature $record): bool => $this->isExternalStep($record)
                            && $this->linkState($record) !== 'issued')
                        ->requiresConfirmation()
                        ->modalDescription('Tautan pengesahan dikirim ke email signer industri yang sudah terverifikasi.')
                        ->action(fn (DocumentSignature $record) => $this->run(
                            fn () => app(IssueSignerLink::class)->handle(auth()->user(), $record),
                            'Tautan pengesahan diterbitkan dan dikirim ke email signer.',
                        )),
                    Action::make('reissue')
                        ->label('Terbitkan ulang tautan')
                        ->icon(Heroicon::OutlinedArrowPath)
                        ->visible(fn (DocumentSignature $record): bool => $this->isExternalStep($record)
                            && $this->linkState($record) === 'issued')
                        ->schema([
                            Textarea::make('alasan')
                                ->label('Alasan terbitkan ulang')
                                ->helperText('Minimal 10 karakter.')
                                ->required()
                                ->minLength(10)
                                ->maxLength(2000),
                        ])
                        ->action(fn (DocumentSignature $record, array $data) => $this->run(
                            fn () => app(ReissueSignerLink::class)->handle(auth()->user(), $record, (string) $data['alasan']),
                            'Tautan lama dicabut dan tautan baru dikirim.',
                        )),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('approveBulk')
                        ->label('Setujui TTD step 2')
                        ->icon(Heroicon::OutlinedCheck)
                        ->requiresConfirmation()
                        ->modalDescription('Tiap laporan diproses independen; kegagalan satu laporan tidak membatalkan yang lain.')
                        ->action(function (Collection $records): void {
                            $this->reportBulk(app(BulkApproveSignatures::class)->handle(
                                auth()->user(),
                                $records->pluck('id')->all(),
                            ));
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->emptyStateHeading('Tidak ada laporan yang menunggu tanda tangan Anda')
            ->emptyStateDescription('Laporan muncul setelah guru pembimbing menandatangani laporan final.');
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([EmbeddedTable::make()]);
    }

    /** @return Builder<DocumentSignature> */
    private function signaturesQuery(): Builder
    {
        return DocumentSignature::query()
            ->where('status', 'pending')
            ->whereIn('step_order', [2, 3, 4])
            ->whereHas('pengajuanSiswa.siswa', fn (Builder $student) => $student
                ->where('jurusan_id', auth()->user()->roleScopeId(Role::Kaprog)))
            ->with(['pengajuanSiswa.siswa', 'pengajuanSiswa.company', 'laporanVersion']);
    }

    private function isExternalStep(DocumentSignature $signature): bool
    {
        return in_array((int) $signature->step_order, [3, 4], true);
    }

    private function linkState(DocumentSignature $signature): string
    {
        $type = match ((int) $signature->step_order) {
            3 => MagicLinkService::TYPE_SIGNER_STEP3,
            4 => MagicLinkService::TYPE_SIGNER_STEP4,
            default => null,
        };

        if ($type === null) {
            return 'none';
        }

        if (app(MagicLinkService::class)->activeFor($type, (int) $signature->getKey()) !== null) {
            return 'issued';
        }

        return MagicLink::query()
            ->where('type', $type)
            ->where('reference_id', $signature->getKey())
            ->exists() ? 'expired' : 'none';
    }

    private function run(callable $action, string $success): void
    {
        try {
            $action();
        } catch (ValidationException $exception) {
            Notification::make()
                ->title(collect($exception->errors())->flatten()->first() ?? __('pkl.pengesahan.not_authorized'))
                ->danger()
                ->send();

            return;
        } catch (DocumentIssuanceHeld $exception) {
            Notification::make()->title($exception->getMessage())->warning()->send();

            return;
        }

        Notification::make()->title($success)->success()->send();
    }

    /**
     * @param  array{approved: array<int, int>, failed: array<int, array{id: int, alasan: string}>}  $result
     */
    private function reportBulk(array $result): void
    {
        $failed = $result['failed'];

        $notification = Notification::make()
            ->title(count($result['approved']).' laporan disetujui, '.count($failed).' gagal.')
            ->status($failed === [] ? 'success' : 'warning')
            ->persistent();

        if ($failed !== []) {
            $notification->body(collect($failed)
                ->map(fn (array $failure): string => '#'.$failure['id'].': '.$failure['alasan'])
                ->implode('<br>'));
        }

        $notification->send();
    }
}
