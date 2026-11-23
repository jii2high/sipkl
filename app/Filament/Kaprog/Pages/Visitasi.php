<?php

namespace App\Filament\Kaprog\Pages;

use App\Actions\Visitasi\RequestVisitasi;
use App\Enums\Role;
use App\Models\Company;
use App\Models\SuratTugasVisitasi;
use App\Models\User;
use App\Services\PlottingService;
use App\Support\StatusChip;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
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

/**
 * Pengajuan visitasi Kaprog (F5, bagian Kaprog): ajukan surat tugas untuk guru
 * bimbingan dalam program keahliannya, lalu pantau status dan surat terbit.
 * Sisi guru (unggah dokumentasi) dibangun terpisah di panel guru.
 */
class Visitasi extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationLabel = 'Visitasi';

    protected static ?string $title = 'Visitasi PKL';

    protected static ?int $navigationSort = 3;

    public static function canAccess(): bool
    {
        return session('active_role') === Role::Kaprog->value;
    }

    public function getSubheading(): ?string
    {
        return 'Hubin meninjau pengajuan. Masa berlaku surat dihitung saat surat terbit.';
    }

    public function table(Table $table): Table
    {
        Gate::forUser(auth()->user())->authorize('viewAny', SuratTugasVisitasi::class);

        return $table
            ->query(SuratTugasVisitasi::query()
                ->with(['guru', 'companies.company'])
                ->where('kaprog_id', auth()->user()->getKey()))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('guru.name')
                    ->label('Guru pembimbing'),
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
                    ->placeholder('Belum terbit'),
                TextColumn::make('masa_berlaku')
                    ->label('Masa berlaku')
                    ->state(fn (SuratTugasVisitasi $record): string => match (true) {
                        filled($record->nomor_surat) => $record->tanggal_mulai->format('d/m/Y')
                            .'–'.$record->tanggal_selesai->format('d/m/Y'),
                        $record->status === 'disetujui' => 'Nomor surat menunggu scan TTD Kepsek.',
                        default => 'Dihitung saat surat terbit.',
                    })
                    ->wrap(),
                TextColumn::make('rejection_note')
                    ->label('Alasan ditolak')
                    ->placeholder('-')
                    ->wrap(),
            ])
            ->recordActions([
                Action::make('download')
                    ->label('Unduh surat')
                    ->icon(Heroicon::OutlinedArrowDownTray)
                    ->url(fn (SuratTugasVisitasi $record): string => route('documents.download', [
                        'jenis' => 'surat_tugas_visitasi',
                        'id' => $record->getKey(),
                    ]))
                    ->openUrlInNewTab()
                    ->visible(fn (SuratTugasVisitasi $record): bool => filled($record->nomor_surat)),
            ])
            ->emptyStateHeading('Belum ada surat tugas visitasi')
            ->emptyStateDescription('Ajukan visitasi untuk guru bimbingan, lalu Hubin meninjaunya.');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('create')
                ->label('Ajukan visitasi')
                ->icon(Heroicon::OutlinedPaperAirplane)
                ->modalHeading('Ajukan surat tugas visitasi')
                ->modalDescription('Masa berlaku default tujuh hari kalender sejak surat terbit.')
                ->modalSubmitActionLabel('Kirim pengajuan')
                ->schema([
                    Select::make('guruId')
                        ->label('Guru pembimbing')
                        ->options(fn (): array => $this->teacherOptions())
                        ->searchable()
                        ->required()
                        ->live(),
                    Select::make('companyIds')
                        ->label('Perusahaan')
                        ->options(fn (Get $get): array => $this->companyOptions($get('guruId')))
                        ->multiple()
                        ->required(fn (Get $get): bool => ! $get('isGeneral'))
                        ->visible(fn (Get $get): bool => ! $get('isGeneral')),
                    Toggle::make('isGeneral')
                        ->label('Berlaku untuk seluruh perusahaan bimbingan guru')
                        ->live(),
                ])
                ->action(function (array $data): void {
                    $guru = is_numeric($data['guruId'] ?? null)
                        ? User::query()->find((int) $data['guruId'])
                        : null;

                    if (! $guru instanceof User) {
                        Notification::make()->title('Pilih guru pembimbing yang masih aktif.')->danger()->send();

                        return;
                    }

                    try {
                        app(RequestVisitasi::class)->handle(
                            $guru,
                            array_map('intval', $data['companyIds'] ?? []),
                            (bool) ($data['isGeneral'] ?? false),
                            auth()->user(),
                        );
                    } catch (ValidationException $exception) {
                        Notification::make()
                            ->title('Pengajuan visitasi belum valid')
                            ->body(collect($exception->errors())->flatten()->implode('<br>'))
                            ->danger()
                            ->send();

                        return;
                    }

                    Notification::make()->title('Pengajuan visitasi dikirim ke Hubin.')->success()->send();
                }),
        ];
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([EmbeddedTable::make()]);
    }

    /** @return array<int, string> */
    private function teacherOptions(): array
    {
        return app(PlottingService::class)->guruCandidatesForKaprog(auth()->user())
            ->mapWithKeys(fn (User $guru): array => [$guru->id => $guru->name])
            ->all();
    }

    /** @return array<int, string> */
    private function companyOptions(mixed $guruId): array
    {
        if (! is_numeric($guruId)) {
            return [];
        }

        $guru = User::query()->find((int) $guruId);

        if (! $guru instanceof User) {
            return [];
        }

        return app(PlottingService::class)->companiesForGuru($guru)
            ->mapWithKeys(fn (Company $company): array => [$company->id => $company->nama])
            ->all();
    }
}
