<?php

namespace App\Filament\Kaprog\Pages;

use App\Actions\Plotting\AssignGuru;
use App\Actions\Plotting\IssueSuratTugasPembimbing;
use App\Enums\Role;
use App\Exceptions\DocumentIssuanceHeld;
use App\Models\AcademicPeriod;
use App\Models\Company;
use App\Models\User;
use App\Services\PlottingService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/**
 * Plotting guru pembimbing Kaprog (F6): satu perusahaan-periode dipegang satu
 * guru. Ganti guru menerbitkan surat baru dan audit di AssignGuru/ReplaceGuru;
 * peringatan multi-wilayah hanya tampil, keputusan tetap di Action domain.
 */
class PlottingGuru extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationLabel = 'Plotting guru';

    protected static ?string $title = 'Plotting Guru Pembimbing';

    protected static ?int $navigationSort = 8;

    /** Periode yang sedang dilihat; menentukan perusahaan dan guru yang siap. */
    public ?int $periodId = null;

    public static function canAccess(): bool
    {
        return session('active_role') === Role::Kaprog->value;
    }

    public function mount(): void
    {
        $this->periodId = AcademicPeriod::query()
            ->where('is_active', true)
            ->orderByDesc('tanggal_mulai')
            ->value('id');
    }

    public function getSubheading(): ?string
    {
        return 'Perusahaan muncul setelah memiliki siswa berstatus sedang PKL pada periode terpilih.';
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('periodId')
                ->label('Periode akademik')
                ->options(fn (): array => $this->periods())
                ->placeholder('Pilih periode')
                ->live(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->records(fn (): Collection => $this->companies())
            ->columns([
                TextColumn::make('nama')
                    ->label('Perusahaan')
                    ->wrap(),
                TextColumn::make('jumlah_siswa')
                    ->label('Siswa PKL aktif')
                    ->badge()
                    ->color('gray'),
                TextColumn::make('guru_pembimbing')
                    ->label('Guru pembimbing')
                    ->state(fn (Company $record): ?string => $record->plottings->first()?->guru?->name)
                    ->placeholder('Belum diplot'),
                TextColumn::make('surat_tugas')
                    ->label('Surat tugas')
                    ->state(fn (Company $record): string => match (true) {
                        filled($record->plottings->first()?->suratTugas?->nomor_surat) => $record->plottings->first()->suratTugas->nomor_surat,
                        $record->plottings->first() !== null => 'Menunggu scan TTD Kepsek',
                        default => 'Belum terbit',
                    }),
            ])
            ->recordActions([
                Action::make('assign')
                    ->label(fn (Company $record): string => $record->plottings->isEmpty() ? 'Plotting guru' : 'Ganti guru')
                    ->icon(Heroicon::OutlinedUserPlus)
                    ->modalHeading('Plotting guru pembimbing')
                    ->modalSubmitActionLabel('Simpan plotting')
                    ->fillForm(fn (Company $record): array => [
                        'guruId' => $record->plottings->first()?->guru_id,
                    ])
                    ->schema(fn (Company $record): array => [
                        Select::make('guruId')
                            ->label('Guru pembimbing')
                            ->options(fn (): array => $this->teacherOptions())
                            ->searchable()
                            ->required()
                            ->live(),
                        Text::make(fn (Get $get): ?string => $this->multiWilayahWarning($get('guruId')))
                            ->color('warning')
                            ->visible(fn (Get $get): bool => filled($this->multiWilayahWarning($get('guruId')))),
                        Text::make('Perusahaan menerima siswa dari beberapa program keahlian. Plotting tetap berlaku untuk perusahaan dan periode ini.')
                            ->color('warning')
                            ->visible($record->jumlah_jurusan > 1),
                    ])
                    ->action(function (Company $record, array $data): void {
                        $period = AcademicPeriod::query()->findOrFail($this->periodId);
                        $guru = User::query()->findOrFail($data['guruId']);

                        try {
                            app(AssignGuru::class)->handle($record, $period, $guru, auth()->user());
                        } catch (ValidationException $exception) {
                            Notification::make()
                                ->title('Plotting belum bisa disimpan')
                                ->body(collect($exception->errors())->flatten()->implode('<br>'))
                                ->danger()
                                ->send();

                            return;
                        }

                        Notification::make()->title('Plotting guru berhasil disimpan.')->success()->send();
                    }),
                Action::make('issue')
                    ->label('Coba terbitkan surat')
                    ->icon(Heroicon::OutlinedArrowPath)
                    ->visible(fn (Company $record): bool => $record->plottings->first() !== null
                        && $record->plottings->first()->suratTugas === null)
                    ->action(function (Company $record): void {
                        try {
                            app(IssueSuratTugasPembimbing::class)->handle($record->plottings->first(), auth()->user());
                        } catch (DocumentIssuanceHeld $exception) {
                            Notification::make()->title($exception->getMessage())->warning()->send();

                            return;
                        }

                        Notification::make()->title('Surat tugas pembimbing diterbitkan.')->success()->send();
                    }),
                Action::make('download')
                    ->label('Unduh surat')
                    ->icon(Heroicon::OutlinedArrowDownTray)
                    ->url(fn (Company $record): string => route('documents.download', [
                        'jenis' => 'surat_tugas_pembimbing',
                        'id' => $record->plottings->first()->suratTugas->getKey(),
                    ]))
                    ->openUrlInNewTab()
                    ->visible(fn (Company $record): bool => filled($record->plottings->first()?->suratTugas?->nomor_surat)),
            ])
            ->paginated(false)
            ->emptyStateHeading('Belum ada perusahaan siap plotting')
            ->emptyStateDescription('Perusahaan muncul setelah ada siswa PKL aktif pada periode ini.');
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            EmbeddedSchema::make('form'),
            EmbeddedTable::make(),
        ]);
    }

    /** @return Collection<int, Company> */
    private function companies(): Collection
    {
        $period = $this->periodId === null ? null : AcademicPeriod::query()->find($this->periodId);

        return $period === null ? collect() : app(PlottingService::class)->eligibleCompanies($period, auth()->user());
    }

    /** @return Collection<int, User> */
    private function teachers(): Collection
    {
        $period = $this->periodId === null ? null : AcademicPeriod::query()->find($this->periodId);

        return $period === null ? collect() : app(PlottingService::class)->guruCandidates($period);
    }

    /** @return array<int, string> */
    private function teacherOptions(): array
    {
        return $this->teachers()
            ->mapWithKeys(fn (User $guru): array => [$guru->id => $guru->name.' · '.$guru->jumlah_bimbingan.' perusahaan'])
            ->all();
    }

    /** @return array<int, string> */
    private function periods(): array
    {
        return AcademicPeriod::query()
            ->orderByDesc('tanggal_mulai')
            ->get()
            ->mapWithKeys(fn (AcademicPeriod $period): array => [
                (int) $period->getKey() => $period->tahun_ajaran.' · batch '.$period->batch,
            ])
            ->all();
    }

    /** Peringatan lunak saat guru terpilih sudah membimbing di alamat berbeda. */
    private function multiWilayahWarning(mixed $guruId): ?string
    {
        if (! is_numeric($guruId)) {
            return null;
        }

        $guru = $this->teachers()->firstWhere('id', (int) $guruId);

        if ($guru === null || $guru->jumlah_wilayah <= 1) {
            return null;
        }

        return 'Guru sudah membimbing di alamat perusahaan yang berbeda. Periksa jarak perjalanan sebelum menetapkan penugasan baru.';
    }
}
