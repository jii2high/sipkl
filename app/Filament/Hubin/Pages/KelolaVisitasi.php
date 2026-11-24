<?php

namespace App\Filament\Hubin\Pages;

use App\Actions\Visitasi\ApproveVisitasi;
use App\Actions\Visitasi\IssueSuratTugasVisitasi;
use App\Actions\Visitasi\RejectVisitasi;
use App\Actions\Visitasi\RequestVisitasi;
use App\Enums\Role;
use App\Models\Company;
use App\Models\SuratTugasVisitasi;
use App\Models\User;
use App\Services\PlottingService;
use App\Support\StatusChip;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Pengelolaan surat tugas visitasi Hubin (T4.3, F5): ajukan manual, setujui,
 * tolak beralasan, dan terbitkan nomor surat. Semua keputusan ada di Action Visitasi.
 */
class KelolaVisitasi extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationLabel = 'Visitasi';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMapPin;

    protected static ?string $title = 'Pengelolaan Visitasi';

    protected static ?int $navigationSort = 3;

    public static function canAccess(): bool
    {
        return session('active_role') === Role::Hubin->value;
    }

    public function table(Table $table): Table
    {
        Gate::forUser(auth()->user())->authorize('viewAny', SuratTugasVisitasi::class);

        return $table
            ->query(SuratTugasVisitasi::query()->with(['guru', 'kaprog', 'companies.company']))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('guru.name')
                    ->label('Guru pembimbing')
                    ->searchable(),
                TextColumn::make('kaprog.name')
                    ->label('Pengaju')
                    ->placeholder('Hubin')
                    ->toggleable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => StatusChip::label($state))
                    ->color(fn (string $state): string => StatusChip::color($state)),
                TextColumn::make('perusahaan')
                    ->label('Perusahaan')
                    ->state(fn (SuratTugasVisitasi $record): string => $record->is_general
                        ? 'Seluruh perusahaan bimbingan'
                        : $record->companies->pluck('company.nama')->filter()->join(', '))
                    ->wrap(),
                TextColumn::make('nomor_surat')
                    ->label('Nomor surat')
                    ->placeholder('Belum terbit')
                    ->searchable(),
                TextColumn::make('masa_berlaku')
                    ->label('Masa berlaku')
                    ->state(fn (SuratTugasVisitasi $record): string => match (true) {
                        filled($record->nomor_surat) => $record->tanggal_mulai->format('d/m/Y')
                            .'–'.$record->tanggal_selesai->format('d/m/Y'),
                        $record->status === 'disetujui' => 'Nomor surat menunggu scan TTD Kepsek.',
                        default => 'Masa berlaku dihitung saat surat terbit.',
                    })
                    ->wrap(),
                TextColumn::make('rejection_note')
                    ->label('Alasan ditolak')
                    ->placeholder('-')
                    ->wrap(),
            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('approve')
                        ->label('Setujui')
                        ->icon(Heroicon::OutlinedCheck)
                        ->requiresConfirmation()
                        ->visible(fn (SuratTugasVisitasi $record): bool => $record->status === 'diajukan')
                        ->action(fn (SuratTugasVisitasi $record) => $this->runAction(
                            fn () => app(ApproveVisitasi::class)->handle($record, auth()->user()),
                            'Visitasi disetujui.',
                        )),
                    Action::make('reject')
                        ->label('Tolak')
                        ->icon(Heroicon::OutlinedXMark)
                        ->color('danger')
                        ->visible(fn (SuratTugasVisitasi $record): bool => $record->status === 'diajukan')
                        ->schema([
                            Textarea::make('reason')
                                ->label('Alasan penolakan')
                                ->required()
                                ->minLength((int) config('pkl.rejection_note.min_length', 10))
                                ->maxLength(500),
                        ])
                        ->action(fn (SuratTugasVisitasi $record, array $data) => $this->runAction(
                            fn () => app(RejectVisitasi::class)->handle($record, auth()->user(), $data['reason']),
                            'Pengajuan visitasi ditolak.',
                        )),
                    Action::make('issue')
                        ->label('Terbitkan surat')
                        ->icon(Heroicon::OutlinedPaperAirplane)
                        ->visible(fn (SuratTugasVisitasi $record): bool => $record->status === 'disetujui'
                            && blank($record->nomor_surat))
                        ->action(function (SuratTugasVisitasi $record): void {
                            try {
                                app(IssueSuratTugasVisitasi::class)->handle($record, auth()->user());
                            } catch (Throwable $exception) {
                                Notification::make()->title($exception->getMessage())->danger()->send();

                                return;
                            }

                            Notification::make()->title('Nomor surat visitasi berhasil diterbitkan.')->success()->send();
                        }),
                    Action::make('download')
                        ->label('Unduh surat')
                        ->icon(Heroicon::OutlinedArrowDownTray)
                        ->url(fn (SuratTugasVisitasi $record): string => route('documents.download', [
                            'jenis' => 'surat_tugas_visitasi',
                            'id' => $record->getKey(),
                        ]))
                        ->openUrlInNewTab()
                        ->visible(fn (SuratTugasVisitasi $record): bool => filled($record->nomor_surat)),
                ]),
            ])
            ->emptyStateHeading('Belum ada pengajuan visitasi')
            ->emptyStateDescription('Surat tugas manual atau pengajuan Kaprog muncul di sini.');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('create')
                ->label('Buat surat tugas manual')
                ->icon(Heroicon::OutlinedPlus)
                ->modalHeading('Buat surat tugas manual')
                ->modalDescription('Surat manual masuk ke antrean persetujuan yang sama.')
                ->modalSubmitActionLabel('Buat pengajuan')
                ->schema([
                    Select::make('guru_id')
                        ->label('Guru pembimbing')
                        ->options(fn (): array => $this->teachers())
                        ->searchable()
                        ->required()
                        ->live(),
                    Select::make('company_ids')
                        ->label('Perusahaan')
                        ->options(fn (Get $get): array => $this->companiesFor($get('guru_id')))
                        ->multiple()
                        ->required(fn (Get $get): bool => ! $get('is_general'))
                        ->visible(fn (Get $get): bool => ! $get('is_general')),
                    Toggle::make('is_general')
                        ->label('Seluruh perusahaan bimbingan guru')
                        ->live(),
                ])
                ->action(function (array $data): void {
                    $guru = is_numeric($data['guru_id'] ?? null)
                        ? User::query()->find((int) $data['guru_id'])
                        : null;

                    if (! $guru instanceof User) {
                        Notification::make()->title('Pilih guru pembimbing yang masih aktif.')->danger()->send();

                        return;
                    }

                    $this->runAction(
                        fn () => app(RequestVisitasi::class)->handle(
                            $guru,
                            array_map('intval', $data['company_ids'] ?? []),
                            (bool) ($data['is_general'] ?? false),
                            auth()->user(),
                        ),
                        'Surat visitasi manual dibuat untuk persetujuan.',
                    );
                }),
        ];
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([EmbeddedTable::make()]);
    }

    /** @return array<int, string> */
    public function teachers(): array
    {
        return User::query()
            ->where('is_active', true)
            ->whereHas('roles', fn ($query) => $query->where('role', Role::GuruPembimbing->value))
            ->whereHas('plottings')
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    /** @return array<int, string> */
    public function companiesFor(mixed $guruId): array
    {
        $guru = is_numeric($guruId) ? User::query()->find((int) $guruId) : null;

        if (! $guru instanceof User) {
            return [];
        }

        return app(PlottingService::class)->companiesForGuru($guru)
            ->mapWithKeys(fn (Company $company): array => [$company->id => $company->nama])
            ->all();
    }

    private function runAction(callable $callback, string $successTitle): void
    {
        try {
            $callback();
        } catch (ValidationException $exception) {
            Notification::make()
                ->title($exception->errors()[array_key_first($exception->errors())][0] ?? 'Data belum valid')
                ->danger()
                ->send();

            return;
        }

        Notification::make()->title($successTitle)->success()->send();
    }
}
