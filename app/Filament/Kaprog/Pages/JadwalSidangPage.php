<?php

namespace App\Filament\Kaprog\Pages;

use App\Actions\Sidang\IssueExaminerLink;
use App\Actions\Sidang\SaveExternalExaminer;
use App\Actions\Sidang\ScheduleSidang;
use App\Actions\Sidang\ShiftSidang;
use App\Enums\Role;
use App\Models\AcademicPeriod;
use App\Models\ExternalExaminer;
use App\Models\JadwalSidang;
use App\Models\PengajuanSiswa;
use App\Services\MagicLinkService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

/**
 * Penjadwalan sidang Kaprog (F9 alur 1/2/4): tetapkan penguji, jadwalkan dari pool
 * Siap Sidang, terbitkan tautan penguji, lalu geser atau batalkan satu jadwal.
 * Cascade shift tetap di ShiftSidang; Page ini hanya menampilkan daftar konfliknya.
 */
class JadwalSidangPage extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationLabel = 'Jadwal sidang';

    protected static ?string $slug = 'jadwal-sidang';

    protected static ?string $title = 'Jadwal Sidang';

    protected static ?int $navigationSort = 7;

    /** Periode yang sedang dilihat; menentukan pool dan daftar jadwal. */
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
        return 'Jadwalkan siswa Siap Sidang, terbitkan tautan penguji, lalu geser atau batalkan satu jadwal.';
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
            ->query($this->schedulesQuery())
            ->defaultSort('tanggal')
            ->columns([
                TextColumn::make('pengajuanSiswa.siswa.name')
                    ->label('Siswa')
                    ->searchable()
                    ->wrap(),
                TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('urutan')
                    ->label('Urutan')
                    ->badge()
                    ->color('gray'),
                TextColumn::make('externalExaminer.nama')
                    ->label('Penguji')
                    ->placeholder('-'),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        JadwalSidang::STATUS_AKTIF => 'Sesi aktif',
                        JadwalSidang::STATUS_SELESAI => 'Selesai',
                        default => 'Terjadwal',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        JadwalSidang::STATUS_AKTIF => 'warning',
                        JadwalSidang::STATUS_SELESAI => 'success',
                        default => 'info',
                    }),
                TextColumn::make('nilai')
                    ->label('Nilai')
                    ->state(fn (JadwalSidang $record): ?string => $record->submission?->is_submitted
                        ? (string) $record->submission->nilai_total
                        : null)
                    ->placeholder('-'),
            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('issue')
                        ->label('Terbitkan tautan')
                        ->icon(Heroicon::OutlinedLink)
                        ->action(function (JadwalSidang $record): void {
                            try {
                                $result = app(IssueExaminerLink::class)->handle($record, auth()->user());
                            } catch (ValidationException $exception) {
                                $this->notifyValidation($exception, 'Tautan belum bisa diterbitkan');

                                return;
                            }

                            Notification::make()
                                ->title(__('pkl.sidang.link_issued'))
                                ->body('PIN: '.$result['pin'])
                                ->persistent()
                                ->success()
                                ->actions(filled($result['wa_url'])
                                    ? [Action::make('wa')->label('Kirim WhatsApp')->button()->url($result['wa_url'], shouldOpenInNewTab: true)]
                                    : [])
                                ->send();
                        }),
                    Action::make('revoke')
                        ->label('Cabut tautan')
                        ->icon(Heroicon::OutlinedNoSymbol)
                        ->color('danger')
                        ->schema([
                            Textarea::make('reason')
                                ->label('Alasan pencabutan (minimal 10 karakter)')
                                ->required()
                                ->minLength((int) config('pkl.rejection_note.min_length', 10))
                                ->maxLength(500),
                        ])
                        ->action(function (JadwalSidang $record, array $data): void {
                            $link = app(MagicLinkService::class)->activeFor(
                                MagicLinkService::TYPE_EXAMINER_SIDANG,
                                (int) $record->getKey(),
                            );

                            if ($link === null) {
                                Notification::make()->title(__('pkl.sidang.link_missing'))->warning()->send();

                                return;
                            }

                            app(MagicLinkService::class)->revoke($link, auth()->user(), $data['reason']);

                            Notification::make()->title('Tautan penguji dicabut.')->success()->send();
                        }),
                    Action::make('shift')
                        ->label('Pindah')
                        ->icon(Heroicon::OutlinedArrowRight)
                        ->schema([
                            DatePicker::make('tanggal')
                                ->label('Tanggal baru')
                                ->required(),
                        ])
                        ->action(fn (JadwalSidang $record, array $data) => $this->shift($record, $data['tanggal'])),
                    Action::make('cancel')
                        ->label('Batalkan jadwal')
                        ->icon(Heroicon::OutlinedTrash)
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalDescription('Sisa antrean pada tanggal tersebut akan bergeser.')
                        ->action(fn (JadwalSidang $record) => $this->shift($record, null)),
                ]),
            ])
            ->emptyStateHeading('Belum ada jadwal sidang')
            ->emptyStateDescription('Jadwalkan siswa dari pool Siap Sidang pada periode ini.');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('examiner')
                ->label('Tetapkan penguji')
                ->icon(Heroicon::OutlinedUserPlus)
                ->modalHeading('Tetapkan penguji')
                ->modalDescription('Penguji menerima tautan dan PIN 4 digit; tidak perlu akun panel.')
                ->modalSubmitActionLabel('Simpan penguji')
                ->schema([
                    TextInput::make('nama')
                        ->label('Nama penguji')
                        ->required()
                        ->maxLength(150),
                    TextInput::make('nik')
                        ->label('NIK (opsional)')
                        ->maxLength(30),
                    Select::make('contactType')
                        ->label('Jenis kontak')
                        ->options(['email' => 'Email', 'whatsapp' => 'WhatsApp'])
                        ->default('email')
                        ->required()
                        ->live(),
                    TextInput::make('contactValue')
                        ->label(fn (Get $get): string => $get('contactType') === 'whatsapp' ? 'Nomor WhatsApp' : 'Email')
                        ->required()
                        ->maxLength(150),
                ])
                ->action(function (array $data): void {
                    try {
                        app(SaveExternalExaminer::class)->handle(
                            $data['nama'],
                            $data['contactType'],
                            $data['contactValue'],
                            auth()->user(),
                            $data['nik'] ?? null,
                        );
                    } catch (ValidationException $exception) {
                        $this->notifyValidation($exception, 'Penguji belum bisa disimpan');

                        return;
                    }

                    Notification::make()->title('Penguji ditetapkan.')->success()->send();
                }),
            Action::make('schedule')
                ->label('Jadwalkan sidang')
                ->icon(Heroicon::OutlinedCalendarDays)
                ->modalHeading('Jadwalkan sidang')
                ->modalDescription('Hanya siswa dengan laporan sah yang muncul di pool Siap Sidang.')
                ->modalSubmitActionLabel('Jadwalkan')
                ->schema([
                    Select::make('pengajuanId')
                        ->label('Siswa')
                        ->options(fn (): array => $this->poolOptions())
                        ->searchable()
                        ->required(),
                    Select::make('examinerId')
                        ->label('Penguji')
                        ->options(fn (): array => $this->examinerOptions())
                        ->searchable()
                        ->required(),
                    DatePicker::make('tanggal')
                        ->label('Tanggal sidang')
                        ->required()
                        ->default(fn (): string => now('Asia/Jakarta')->toDateString()),
                ])
                ->action(function (array $data): void {
                    try {
                        app(ScheduleSidang::class)->handle(
                            PengajuanSiswa::query()->findOrFail($data['pengajuanId']),
                            ExternalExaminer::query()->findOrFail($data['examinerId']),
                            $data['tanggal'],
                            auth()->user(),
                        );
                    } catch (ValidationException $exception) {
                        $this->notifyValidation($exception, 'Jadwal belum bisa disimpan');

                        return;
                    }

                    Notification::make()->title(__('pkl.sidang.scheduled'))->success()->send();
                }),
        ];
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            EmbeddedSchema::make('form'),
            EmbeddedTable::make(),
        ]);
    }

    /** Geser satu jadwal, atau batalkan bila `$tanggal` null; konflik tampil apa adanya. */
    private function shift(JadwalSidang $jadwal, ?string $tanggal): void
    {
        try {
            app(ShiftSidang::class)->handle($jadwal, $tanggal, auth()->user());
        } catch (ValidationException $exception) {
            $this->notifyValidation($exception, 'Perubahan jadwal ditolak');

            return;
        }

        Notification::make()
            ->title($tanggal === null ? __('pkl.sidang.cancelled') : __('pkl.sidang.moved'))
            ->success()
            ->send();
    }

    /** @return Builder<JadwalSidang> */
    private function schedulesQuery(): Builder
    {
        $query = JadwalSidang::query()
            ->with(['pengajuanSiswa.siswa', 'externalExaminer', 'submission']);

        if ($this->periodId === null) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereHas('pengajuanSiswa', fn (Builder $pengajuan) => $pengajuan
            ->where('academic_period_id', $this->periodId)
            ->whereHas('siswa', fn (Builder $student) => $student->where('jurusan_id', auth()->user()->roleScopeId(Role::Kaprog))));
    }

    /** @return array<int, string> */
    private function poolOptions(): array
    {
        if ($this->periodId === null) {
            return [];
        }

        return PengajuanSiswa::query()
            ->with(['siswa', 'company'])
            ->where('academic_period_id', $this->periodId)
            ->siapSidang()
            ->whereHas('siswa', fn (Builder $student) => $student->where('jurusan_id', auth()->user()->roleScopeId(Role::Kaprog)))
            ->whereDoesntHave('jadwalSidang')
            ->orderBy('id')
            ->get()
            ->mapWithKeys(fn (PengajuanSiswa $pengajuan): array => [
                (int) $pengajuan->getKey() => ($pengajuan->siswa?->name ?? '-').' — '.($pengajuan->company?->nama ?? '-'),
            ])
            ->all();
    }

    /** @return array<int, string> */
    private function examinerOptions(): array
    {
        return ExternalExaminer::query()
            ->where('jurusan_id', auth()->user()->roleScopeId(Role::Kaprog))
            ->orderBy('nama')
            ->pluck('nama', 'id')
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

    /** Semua pesan validasi tampil sekaligus, termasuk daftar konflik cascade shift. */
    private function notifyValidation(ValidationException $exception, string $title): void
    {
        Notification::make()
            ->title($title)
            ->body(collect($exception->errors())->flatten()->implode('<br>'))
            ->danger()
            ->send();
    }
}
