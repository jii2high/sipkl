<?php

namespace App\Filament\App\Pages;

use App\Actions\Form\CreateFormDefinition;
use App\Actions\Form\DuplicateFormDefinition;
use App\Actions\Form\PublishFormDefinition;
use App\Actions\Form\SaveFormStructure;
use App\Models\AcademicPeriod;
use App\Models\FormDefinition;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/**
 * Builder form penilaian Kaprog (F8): Section → Items per jurusan + periode.
 * Struktur disimpan relasional (form_sections/form_items); struktur terkunci
 * setelah ada submission, perubahan dilakukan lewat versi baru.
 */
class FormBuilder extends Page
{
    protected string $view = 'filament.app.pages.form-builder';

    protected static ?string $navigationLabel = 'Form penilaian';

    protected static ?string $title = 'Form Builder';

    protected static ?int $navigationSort = 6;

    public ?int $periodId = null;

    public ?int $definitionId = null;

    public string $nama = '';

    public string $formType = FormDefinition::TYPE_SIDANG;

    public bool $hasKehadiran = false;

    public bool $hasCatatan = false;

    /** @var array<int, array{label: string, items: array<int, array{label: string, has_alokasi_waktu: bool}>}> */
    public array $sections = [];

    public ?string $notice = null;

    public static function canAccess(): bool
    {
        return session('active_role') === 'kaprog';
    }

    public function mount(): void
    {
        $this->periodId = AcademicPeriod::query()
            ->orderByDesc('is_active')
            ->orderByDesc('tanggal_mulai')
            ->value('id');
    }

    /** @return array<int, string> */
    public function periods(): array
    {
        return AcademicPeriod::query()
            ->orderByDesc('tanggal_mulai')
            ->get()
            ->mapWithKeys(fn (AcademicPeriod $period): array => [
                $period->getKey() => $period->tahun_ajaran.' · batch '.$period->batch,
            ])
            ->all();
    }

    /** @return Collection<int, FormDefinition> */
    public function definitions(): Collection
    {
        if ($this->periodId === null) {
            return collect();
        }

        return FormDefinition::query()
            ->where('jurusan_id', auth()->user()->roleScopeId('kaprog'))
            ->where('academic_period_id', $this->periodId)
            ->withCount('items')
            ->orderBy('form_type')
            ->orderByDesc('id')
            ->get();
    }

    public function newDefinition(): void
    {
        $this->definitionId = null;
        $this->nama = '';
        $this->formType = FormDefinition::TYPE_SIDANG;
        $this->hasKehadiran = false;
        $this->hasCatatan = false;
        $this->notice = null;
        $this->sections = [[
            'label' => 'Aspek penilaian',
            'items' => [['label' => '', 'has_alokasi_waktu' => false]],
        ]];
    }

    public function editDefinition(int $id): void
    {
        $definition = $this->findDefinition($id);

        $this->definitionId = $definition->getKey();
        $this->nama = $definition->nama;
        $this->formType = $definition->form_type;
        $this->hasKehadiran = $definition->has_kehadiran;
        $this->hasCatatan = $definition->has_catatan;
        $this->notice = $definition->isLocked() ? __('pkl.form.locked') : null;
        $this->sections = $definition->sections()
            ->with('items')
            ->get()
            ->map(fn ($section): array => [
                'label' => $section->label,
                'items' => $section->items->map(fn ($item): array => [
                    'label' => $item->label,
                    'has_alokasi_waktu' => $item->has_alokasi_waktu,
                ])->all(),
            ])
            ->all();
    }

    public function addSection(): void
    {
        $this->sections[] = ['label' => '', 'items' => [['label' => '', 'has_alokasi_waktu' => false]]];
    }

    public function removeSection(int $index): void
    {
        unset($this->sections[$index]);
        $this->sections = array_values($this->sections);
    }

    public function addItem(int $index): void
    {
        $this->sections[$index]['items'][] = ['label' => '', 'has_alokasi_waktu' => false];
    }

    public function removeItem(int $index, int $itemIndex): void
    {
        unset($this->sections[$index]['items'][$itemIndex]);
        $this->sections[$index]['items'] = array_values($this->sections[$index]['items']);
    }

    public function save(): void
    {
        $definition = $this->persist();

        if ($definition !== null) {
            Notification::make()->title(__('pkl.form.saved'))->success()->send();
        }
    }

    public function saveAndPublish(): void
    {
        $definition = $this->persist();

        if ($definition === null) {
            return;
        }

        try {
            app(PublishFormDefinition::class)->handle($definition, auth()->user());
        } catch (ValidationException $exception) {
            $this->addError('sections', collect($exception->errors())->flatten()->first() ?? '');

            return;
        }

        $this->definitionId = $definition->getKey();
        Notification::make()->title(__('pkl.form.published'))->success()->send();
    }

    public function createVersion(): void
    {
        if ($this->definitionId === null) {
            return;
        }

        $copy = app(DuplicateFormDefinition::class)->handle(
            $this->findDefinition($this->definitionId),
            auth()->user(),
        );

        $this->editDefinition((int) $copy->getKey());
        Notification::make()->title(__('pkl.form.version_created'))->success()->send();
    }

    /** Buat/ubah definisi; null bila validasi gagal. */
    private function persist(): ?FormDefinition
    {
        $this->validate([
            'periodId' => ['required', 'integer', 'exists:academic_periods,id'],
            'nama' => ['required', 'string', 'max:150'],
            'formType' => ['required', 'in:sidang,penilaian_industri'],
            'sections' => ['required', 'array', 'min:1'],
            'sections.*.label' => ['required', 'string', 'max:150'],
            'sections.*.items' => ['array'],
            'sections.*.items.*.label' => ['required', 'string', 'max:255'],
        ], [
            'periodId.required' => __('pkl.form.period_required'),
            'nama.required' => __('pkl.form.nama_required'),
            'sections.*.label.required' => __('pkl.form.section_label_required'),
            'sections.*.items.*.label.required' => __('pkl.form.item_label_required'),
        ]);

        $kaprog = auth()->user();
        $period = AcademicPeriod::query()->findOrFail($this->periodId);

        try {
            if ($this->definitionId === null) {
                $definition = app(CreateFormDefinition::class)->handle(
                    $period,
                    $this->formType,
                    $this->nama,
                    $kaprog,
                    $this->hasKehadiran,
                    $this->hasCatatan,
                );
                $this->definitionId = $definition->getKey();
            } else {
                $definition = $this->findDefinition($this->definitionId);
            }

            $definition = app(SaveFormStructure::class)->handle($definition, $this->sections, $kaprog);
        } catch (ValidationException $exception) {
            $this->addError('sections', collect($exception->errors())->flatten()->first() ?? '');

            return null;
        }

        $this->notice = null;

        return $definition;
    }

    private function findDefinition(int $id): FormDefinition
    {
        return FormDefinition::query()
            ->where('jurusan_id', auth()->user()->roleScopeId('kaprog'))
            ->findOrFail($id);
    }
}
