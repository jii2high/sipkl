<?php

namespace App\Filament\Guru\Pages;

use App\Actions\Visitasi\UploadDokumentasi;
use App\Enums\Role;
use App\Models\Company;
use App\Models\SuratTugasVisitasi;
use App\Models\VisitasiDokumentasi;
use App\Services\PlottingService;
use App\Support\JakartaTime;
use App\Support\StatusChip;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
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
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/**
 * Sisi guru pada visitasi PKL (F5): pantau surat tugas yang menyebut dirinya, lalu
 * unggah dokumentasi kunjungan selama masa berlaku surat. Pengajuan surat tugas
 * tetap di Kaprog/Hubin. Aturan (surat bernomor, rentang tanggal, kepemilikan
 * perusahaan) tetap di Action Visitasi.
 */
class Visitasi extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationLabel = 'Visitasi';

    protected static ?string $title = 'Visitasi PKL';

    protected static ?int $navigationSort = 3;

    public static function canAccess(): bool
    {
        return session('active_role') === Role::GuruPembimbing->value;
    }

    public function getSubheading(): ?string
    {
        return 'Unggah dokumentasi kunjungan setelah surat tugas terbit, selama masa berlakunya.';
    }

    public function table(Table $table): Table
    {
        Gate::forUser(auth()->user())->authorize('viewAny', SuratTugasVisitasi::class);

        return $table
            ->query(SuratTugasVisitasi::query()
                ->with(['companies.company', 'dokumentasi.company'])
                ->where('guru_id', auth()->user()->getKey()))
            ->defaultSort('created_at', 'desc')
            ->columns([
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
                TextColumn::make('dokumentasi')
                    ->label('Dokumentasi kunjungan')
                    ->html()
                    ->state(fn (SuratTugasVisitasi $record): string => $record->dokumentasi
                        ->map(fn (VisitasiDokumentasi $item): string => '<a class="fi-link" href="'
                            .e(route('visitasi.documentation.download', ['id' => $item->getKey()])).'">'
                            .e(($item->company?->nama ?? 'Perusahaan').' · '.$item->tanggal_kunjungan->format('d/m/Y')).'</a>')
                        ->join('<br>'))
                    ->placeholder('Belum ada')
                    ->wrap()
                    ->toggleable(),
                TextColumn::make('rejection_note')
                    ->label('Alasan ditolak')
                    ->placeholder('-')
                    ->wrap()
                    ->toggleable(),
            ])
            ->recordActions([
                Action::make('upload')
                    ->label('Unggah dokumentasi')
                    ->icon(Heroicon::OutlinedArrowUpTray)
                    ->modalHeading('Unggah dokumentasi kunjungan')
                    ->modalDescription('Gunakan foto JPG atau PNG, maksimal 5 MB.')
                    ->visible(fn (SuratTugasVisitasi $record): bool => $this->canUpload($record))
                    ->schema([
                        Select::make('companyId')
                            ->label('Perusahaan')
                            ->options(fn (SuratTugasVisitasi $record): array => $this->companyOptions($record))
                            ->required(),
                        DatePicker::make('visitDate')
                            ->label('Tanggal kunjungan')
                            ->default(fn (): string => JakartaTime::today()->toDateString())
                            ->required(),
                        FileUpload::make('photo')
                            ->label('Foto dokumentasi')
                            ->image()
                            ->acceptedFileTypes(['image/jpeg', 'image/png'])
                            ->maxSize(5 * 1024)
                            ->storeFiles(false)
                            ->required(),
                        Textarea::make('note')
                            ->label('Keterangan (opsional)')
                            ->maxLength(2000),
                    ])
                    ->action(function (SuratTugasVisitasi $record, array $data): void {
                        try {
                            app(UploadDokumentasi::class)->handle(
                                $record,
                                (int) $data['companyId'],
                                (string) $data['visitDate'],
                                $data['photo'],
                                auth()->user(),
                                $data['note'] ?? null,
                            );
                        } catch (ValidationException $exception) {
                            Notification::make()
                                ->title(collect($exception->errors())->flatten()->first() ?? 'Data belum valid')
                                ->danger()
                                ->send();

                            return;
                        }

                        Notification::make()->title('Dokumentasi visitasi berhasil diunggah.')->success()->send();
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
            ])
            ->emptyStateHeading('Belum ada surat tugas visitasi')
            ->emptyStateDescription('Surat tugas untuk Anda muncul di sini setelah Kaprog atau Hubin mengajukannya.');
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([EmbeddedTable::make()]);
    }

    private function canUpload(SuratTugasVisitasi $record): bool
    {
        return filled($record->nomor_surat)
            && in_array($record->status, ['disetujui', 'dicetak', 'aktif'], true)
            && $record->tanggal_selesai->toDateString() >= JakartaTime::today()->toDateString();
    }

    /** @return array<int, string> */
    private function companyOptions(SuratTugasVisitasi $record): array
    {
        if ($record->is_general) {
            return app(PlottingService::class)->companiesForGuru(auth()->user())
                ->mapWithKeys(fn (Company $company): array => [$company->id => $company->nama])
                ->all();
        }

        return $record->companies()
            ->with('company')
            ->get()
            ->mapWithKeys(fn ($row): array => [$row->company_id => $row->company?->nama ?? 'Perusahaan'])
            ->all();
    }
}
