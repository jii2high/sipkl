<?php

namespace App\Filament\Kaprog\Pages;

use App\Actions\Form\CreateFormDefinition;
use App\Actions\Form\DuplicateFormDefinition;
use App\Actions\Form\PublishFormDefinition;
use App\Actions\Form\SaveFormStructure;
use App\Enums\Role;
use App\Models\AcademicPeriod;
use App\Models\FormDefinition;
use App\Models\FormItem;
use App\Models\FormSection;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Validation\ValidationException;

/**
 * Builder form penilaian Kaprog (F8). Daftar definisi tampil native; struktur
 * Section → Item disusun lewat Repeater dan disimpan hanya lewat Action domain
 * (`SaveFormStructure`), sehingga guard terkunci (BR-F8-1) dan audit tetap jalan.
 */
class FormBuilder extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationLabel = 'Form penilaian';

    protected static ?string $title = 'Form Builder';

    protected static ?int $navigationSort = 6;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    /** Definisi yang sedang dibuka di editor; null bila belum ada. */
    public ?int $definitionId = null;

    /** State form editor (statePath `data`), hanya berisi `sections`. */
    public array $data = ['sections' => []];

    /** @var array<int, string>|null Cache label periode per request. */
    private ?array $periodLabels = null;

    public static function canAccess(): bool
    {
        return session('active_role') === Role::Kaprog->value;
    }

    public function getSubheading(): ?string
    {
        return 'Susun section dan item form penilaian. Struktur terkunci setelah ada pengisian; perubahan berikutnya lewat versi baru.';
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Struktur form')
                    ->description(fn (): ?string => $this->editorDescription())
                    ->visible(fn (): bool => $this->definitionId !== null)
                    ->schema([
                        Repeater::make('sections')
                            ->schema([
                                TextInput::make('label')
                                    ->label('Judul section')
                                    ->required()
                                    ->maxLength(150),
                                Repeater::make('items')
                                    ->label('Item penilaian')
                                    ->schema([
                                        TextInput::make('label')
                                            ->label('Label item')
                                            ->required()
                                            ->maxLength(255),
                                        Toggle::make('has_alokasi_waktu')
                                            ->label('Alokasi waktu'),
                                    ])
                                    ->columns(2)
                                    ->defaultItems(1)
                                    ->addActionLabel('Tambah item')
                                    ->reorderableWithButtons(),
                            ])
                            ->defaultItems(1)
                            ->addActionLabel('Tambah section')
                            ->reorderableWithButtons(),
                    ]),
            ])
            ->statePath('data')
            ->disabled(fn (): bool => $this->selectedDefinition()?->isLocked() ?? false);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                FormDefinition::query()
                    ->where('jurusan_id', auth()->user()?->roleScopeId(Role::Kaprog))
                    ->withCount(['items', 'submissions'])
            )
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('nama')
                    ->label('Nama form')
                    ->searchable()
                    ->wrap(),
                TextColumn::make('form_type')
                    ->label('Jenis')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => $state === FormDefinition::TYPE_INDUSTRI
                        ? 'Penilaian industri'
                        : 'Sidang'),
                TextColumn::make('periode')
                    ->label('Periode')
                    ->state(fn (FormDefinition $record): string => $this->periods()[(int) $record->academic_period_id] ?? '-'),
                TextColumn::make('items_count')
                    ->label('Item')
                    ->badge(),
                TextColumn::make('is_published')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Dipublikasikan' : 'Draf')
                    ->color(fn (bool $state): string => $state ? 'success' : 'gray'),
                IconColumn::make('locked')
                    ->label('Terkunci')
                    ->state(fn (FormDefinition $record): bool => $record->submissions_count > 0)
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('academic_period_id')
                    ->label('Periode')
                    ->options(fn (): array => $this->periods()),
            ])
            ->recordActions([
                Action::make('open')
                    ->label('Buka struktur')
                    ->icon(Heroicon::OutlinedPencilSquare)
                    ->action(function (FormDefinition $record): void {
                        $this->openDefinition($record);
                    }),
                Action::make('newVersion')
                    ->label('Buat versi baru')
                    ->icon(Heroicon::OutlinedDocumentDuplicate)
                    ->requiresConfirmation()
                    ->modalDescription(__('pkl.form.locked'))
                    ->visible(fn (FormDefinition $record): bool => $record->submissions_count > 0)
                    ->action(function (FormDefinition $record): void {
                        $copy = app(DuplicateFormDefinition::class)->handle($record, auth()->user());

                        $this->openDefinition($copy);

                        Notification::make()->title(__('pkl.form.version_created'))->success()->send();
                    }),
            ])
            ->emptyStateHeading('Belum ada form')
            ->emptyStateDescription('Klik "Form baru" untuk menyusun struktur penilaian.');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('new')
                ->label('Form baru')
                ->icon(Heroicon::OutlinedPlus)
                ->schema([
                    Select::make('periodId')
                        ->label('Periode akademik')
                        ->options(fn (): array => $this->periods())
                        ->default(fn (): ?int => array_key_first($this->periods()))
                        ->required(),
                    TextInput::make('nama')
                        ->label('Nama form')
                        ->required()
                        ->maxLength(150),
                    Select::make('formType')
                        ->label('Jenis form')
                        ->options([
                            FormDefinition::TYPE_SIDANG => 'Sidang',
                            FormDefinition::TYPE_INDUSTRI => 'Penilaian industri',
                        ])
                        ->default(FormDefinition::TYPE_SIDANG)
                        ->required(),
                    Toggle::make('hasKehadiran')
                        ->label('Sertakan section Kehadiran'),
                    Toggle::make('hasCatatan')
                        ->label('Sertakan section Catatan'),
                ])
                ->action(function (array $data): void {
                    $period = AcademicPeriod::query()->findOrFail($data['periodId']);

                    $definition = app(CreateFormDefinition::class)->handle(
                        $period,
                        $data['formType'],
                        $data['nama'],
                        auth()->user(),
                        (bool) ($data['hasKehadiran'] ?? false),
                        (bool) ($data['hasCatatan'] ?? false),
                    );

                    $this->openDefinition($definition);

                    Notification::make()->title(__('pkl.form.created'))->success()->send();
                }),
            Action::make('save')
                ->label('Simpan struktur')
                ->icon(Heroicon::OutlinedCheck)
                ->visible(fn (): bool => $this->canEditStructure())
                ->action(function (): void {
                    $this->persistStructure(publish: false);
                }),
            Action::make('publish')
                ->label('Simpan & publikasikan')
                ->icon(Heroicon::OutlinedRocketLaunch)
                ->visible(fn (): bool => $this->canEditStructure()
                    && ! ($this->selectedDefinition()?->is_published ?? false))
                ->action(function (): void {
                    $this->persistStructure(publish: true);
                }),
        ];
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            EmbeddedTable::make(),
            EmbeddedSchema::make('form'),
        ]);
    }

    /** Muat struktur definisi ke editor. Definisi kosong diisi satu section awal. */
    private function openDefinition(FormDefinition $definition): void
    {
        $this->resetValidation();
        $this->definitionId = (int) $definition->getKey();

        $sections = $definition->sections()->with('items')->get()
            ->map(fn (FormSection $section): array => [
                'label' => $section->label,
                'items' => $section->items->map(fn (FormItem $item): array => [
                    'label' => $item->label,
                    'has_alokasi_waktu' => (bool) $item->has_alokasi_waktu,
                ])->all(),
            ])
            ->all();

        if ($sections === [] && ! $definition->isLocked()) {
            $sections = [['label' => '', 'items' => [['label' => '', 'has_alokasi_waktu' => false]]]];
        }

        $this->form->fill(['sections' => $sections]);
    }

    /** Simpan struktur lewat Action domain; `$publish` ikut mempublikasikan. */
    private function persistStructure(bool $publish): void
    {
        $definition = $this->selectedDefinition();

        if ($definition === null) {
            return;
        }

        try {
            app(SaveFormStructure::class)->handle(
                $definition,
                $this->form->getState()['sections'] ?? [],
                auth()->user(),
            );

            if ($publish) {
                app(PublishFormDefinition::class)->handle($definition->refresh(), auth()->user());
            }
        } catch (ValidationException $exception) {
            Notification::make()
                ->title(collect($exception->errors())->flatten()->first() ?? __('pkl.form.publish_empty'))
                ->danger()
                ->send();

            return;
        }

        $this->openDefinition($definition->refresh());

        Notification::make()
            ->title($publish ? __('pkl.form.published') : __('pkl.form.saved'))
            ->success()
            ->send();
    }

    private function canEditStructure(): bool
    {
        $definition = $this->selectedDefinition();

        return $definition !== null && ! $definition->isLocked();
    }

    private function selectedDefinition(): ?FormDefinition
    {
        if ($this->definitionId === null) {
            return null;
        }

        return FormDefinition::query()
            ->where('jurusan_id', auth()->user()?->roleScopeId(Role::Kaprog))
            ->find($this->definitionId);
    }

    private function editorDescription(): ?string
    {
        $definition = $this->selectedDefinition();

        if ($definition === null) {
            return null;
        }

        if ($definition->isLocked()) {
            return __('pkl.form.locked');
        }

        return $definition->nama.' · '.($definition->is_published ? 'Dipublikasikan' : 'Draf');
    }

    /** @return array<int, string> */
    private function periods(): array
    {
        return $this->periodLabels ??= AcademicPeriod::query()
            ->orderByDesc('tanggal_mulai')
            ->get()
            ->mapWithKeys(fn (AcademicPeriod $period): array => [
                (int) $period->getKey() => $period->tahun_ajaran.' · batch '.$period->batch,
            ])
            ->all();
    }
}
