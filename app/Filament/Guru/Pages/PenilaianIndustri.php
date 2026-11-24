<?php

namespace App\Filament\Guru\Pages;

use App\Actions\Industri\GenerateIndustryLink;
use App\Actions\Industri\ReissueIndustryLink;
use App\Actions\Industri\RevokeIndustryLink;
use App\Enums\Role;
use App\Models\AcademicPeriod;
use App\Models\Company;
use App\Models\FormDefinition;
use App\Models\FormSubmission;
use App\Models\PengajuanSiswa;
use App\Services\PlottingService;
use App\Support\IndustryWindow;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Validation\ValidationException;

/**
 * Penilaian industri per perusahaan bimbingan guru (F10): satu tautan per form
 * (jurusan) untuk semua siswa bimbingan aktif di perusahaan itu. Daftar baris
 * dicomot dari plotting guru, bukan query langsung, karena baris tanpa submission
 * tetap perlu ditampilkan. Window dan keputusan tetap di Action Industri.
 */
class PenilaianIndustri extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationLabel = 'Penilaian industri';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static ?string $slug = 'penilaian-industri';

    protected static ?string $title = 'Penilaian Industri';

    protected static ?int $navigationSort = 5;

    /** Periode yang sedang dilihat; menentukan perusahaan dan form yang tampil. */
    public ?int $periodId = null;

    public static function canAccess(): bool
    {
        return session('active_role') === Role::GuruPembimbing->value;
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
        return 'Satu tautan berlaku untuk seluruh siswa bimbingan Anda di perusahaan itu, per jurusan. Tautan hanya terbit dalam window H-1 bulan sebelum PKL selesai.';
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
            ->records(fn (): array => $this->rows())
            ->columns([
                TextColumn::make('company')
                    ->label('Perusahaan')
                    ->state(fn (array $record): string => $record['company']->nama)
                    ->wrap()
                    ->lineClamp(2),
                TextColumn::make('definition')
                    ->label('Form')
                    ->state(fn (array $record): string => $record['definition']->nama)
                    ->toggleable(),
                TextColumn::make('students')
                    ->label('Siswa bimbingan')
                    ->state(fn (array $record): string => $record['students']
                        ->map(fn (PengajuanSiswa $student): string => (string) $student->siswa?->name)
                        ->filter()
                        ->join(', '))
                    ->wrap()
                    ->lineClamp(2),
                TextColumn::make('tautan')
                    ->label('Status tautan')
                    ->badge()
                    ->state(fn (array $record): string => $this->stateLabels()[$this->linkState($record)] ?? $this->linkState($record))
                    ->color(fn (array $record): string => match ($this->linkState($record)) {
                        'active' => 'success',
                        'expired' => 'warning',
                        'final' => 'info',
                        default => 'gray',
                    }),
                TextColumn::make('nilai')
                    ->label('Nilai')
                    ->state(fn (array $record): mixed => $record['submission']?->nilai_total)
                    ->placeholder('-'),
                TextColumn::make('window')
                    ->label('Window')
                    ->badge()
                    ->state(fn (array $record): string => $record['window'] ? 'terbuka' : 'belum dibuka')
                    ->color(fn (array $record): string => $record['window'] ? 'success' : 'gray'),
            ])
            ->recordActions([
                Action::make('issue')
                    ->label('Terbitkan tautan')
                    ->icon(Heroicon::OutlinedLink)
                    ->iconButton()
                    ->tooltip('Terbitkan tautan')
                    ->visible(fn (array $record): bool => in_array($this->linkState($record), ['none', 'expired'], true))
                    ->action(fn (array $record) => $this->issue($record)),
                Action::make('revoke')
                    ->label('Cabut tautan')
                    ->icon(Heroicon::OutlinedNoSymbol)
                    ->iconButton()
                    ->tooltip('Cabut tautan')
                    ->color('danger')
                    ->visible(fn (array $record): bool => $this->linkState($record) === 'active')
                    ->schema([$this->reasonField()])
                    ->action(function (array $record, array $data): void {
                        $this->run(
                            fn () => app(RevokeIndustryLink::class)->handle($record['submission'], auth()->user(), $data['reason']),
                            __('pkl.industri.link_revoked'),
                        );
                    }),
                Action::make('reissue')
                    ->label('Terbitkan ulang')
                    ->icon(Heroicon::OutlinedArrowPath)
                    ->iconButton()
                    ->tooltip('Terbitkan ulang')
                    ->visible(fn (array $record): bool => $this->linkState($record) === 'active')
                    ->schema([$this->reasonField()])
                    ->action(function (array $record, array $data): void {
                        $this->run(
                            fn () => $this->announce(app(ReissueIndustryLink::class)->handle($record['submission'], auth()->user(), $data['reason'])['url'], __('pkl.industri.link_reissued')),
                        );
                    }),
            ])
            ->paginated(false)
            ->emptyStateHeading('Belum ada perusahaan bimbingan dengan form penilaian industri')
            ->emptyStateDescription('Perusahaan muncul setelah punya siswa PKL aktif dan form industri terbit di periode ini.');
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            EmbeddedSchema::make('form'),
            EmbeddedTable::make(),
        ]);
    }

    /**
     * @return array<string, array{company: Company, period: AcademicPeriod, definition: FormDefinition, students: mixed, submission: ?FormSubmission, window: bool}>
     */
    private function rows(): array
    {
        $period = $this->periodId === null ? null : AcademicPeriod::query()->find($this->periodId);

        if ($period === null) {
            return [];
        }

        $companies = app(PlottingService::class)->companiesForGuru(auth()->user(), (int) $period->getKey());

        if ($companies->isEmpty()) {
            return [];
        }

        $definitions = FormDefinition::query()
            ->where('academic_period_id', $period->getKey())
            ->where('form_type', FormDefinition::TYPE_INDUSTRI)
            ->where('is_published', true)
            ->orderByDesc('id')
            ->get()
            ->unique('jurusan_id')
            ->keyBy('jurusan_id');

        $students = PengajuanSiswa::query()
            ->with('siswa')
            ->where('academic_period_id', $period->getKey())
            ->where('status', 'sedang_pkl')
            ->whereIn('company_id', $companies->pluck('id'))
            ->orderBy('id')
            ->get()
            ->groupBy(fn (PengajuanSiswa $row): string => $row->company_id.'|'.$row->siswa?->jurusan_id);

        $submissions = FormSubmission::query()
            ->where('form_type', FormDefinition::TYPE_INDUSTRI)
            ->where('academic_period_id', $period->getKey())
            ->whereIn('company_id', $companies->pluck('id'))
            ->get()
            ->keyBy(fn (FormSubmission $row): string => $row->form_definition_id.'|'.$row->company_id);

        $rows = [];

        foreach ($companies as $company) {
            foreach ($students as $key => $group) {
                [$companyId, $jurusanId] = array_pad(explode('|', (string) $key), 2, null);

                if ((int) $companyId !== (int) $company->getKey()) {
                    continue;
                }

                $definition = $definitions->get((int) $jurusanId);

                if ($definition === null) {
                    continue;
                }

                $rows[$company->getKey().'|'.$definition->getKey()] = [
                    'company' => $company,
                    'period' => $period,
                    'definition' => $definition,
                    'students' => $group,
                    'submission' => $submissions->get($definition->getKey().'|'.$company->getKey()),
                    'window' => IndustryWindow::isOpen($company, $period, (int) $jurusanId),
                ];
            }
        }

        return $rows;
    }

    /** @param array<string, mixed> $record */
    private function issue(array $record): void
    {
        $this->run(fn () => $this->announce(app(GenerateIndustryLink::class)->handle(
            $record['company'],
            $record['period'],
            $record['definition'],
            auth()->user(),
        )['url'], __('pkl.industri.link_issued')));
    }

    /** @param array<string, mixed> $record */
    private function linkState(array $record): string
    {
        return $record['submission']?->industryLinkState() ?? 'none';
    }

    private function reasonField(): Textarea
    {
        return Textarea::make('reason')
            ->label('Alasan (minimal 10 karakter)')
            ->required()
            ->minLength((int) config('pkl.rejection_note.min_length', 10))
            ->maxLength(500);
    }

    private function announce(string $url, string $title): void
    {
        Notification::make()
            ->title($title)
            ->body($url.' · '.__('pkl.industri.link_email_note'))
            ->persistent()
            ->success()
            ->send();
    }

    /** @return array<string, string> */
    private function stateLabels(): array
    {
        return [
            'none' => __('pkl.industri.state_none'),
            'active' => __('pkl.industri.state_active'),
            'expired' => __('pkl.industri.state_expired'),
            'final' => __('pkl.industri.state_final'),
        ];
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

    private function run(callable $action, ?string $success = null): void
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

        if ($success !== null) {
            Notification::make()->title($success)->success()->send();
        }
    }
}
