<?php

use App\Actions\Form\CreateFormDefinition;
use App\Actions\Form\DuplicateFormDefinition;
use App\Actions\Form\PublishFormDefinition;
use App\Actions\Form\SaveFormStructure;
use App\Filament\Kaprog\Pages\FormBuilder;
use App\Models\AcademicPeriod;
use App\Models\Company;
use App\Models\FormDefinition;
use App\Models\FormSubmission;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

/** @return array{kaprog: User, period: AcademicPeriod} */
function pklFormContext(): array
{
    $kaprog = pklUser(['kaprog']);
    session(['active_role' => 'kaprog']);
    $period = AcademicPeriod::factory()->create();

    return compact('kaprog', 'period');
}

function pklFormDefinition(
    User $kaprog,
    AcademicPeriod $period,
    string $type = FormDefinition::TYPE_SIDANG,
    bool $kehadiran = true,
    bool $catatan = true,
): FormDefinition {
    $definition = app(CreateFormDefinition::class)->handle(
        $period,
        $type,
        'Form '.$type,
        $kaprog,
        $kehadiran,
        $catatan,
    );

    return app(SaveFormStructure::class)->handle($definition, [
        ['label' => 'Aspek Teknis', 'items' => [['label' => 'Penguasaan materi'], ['label' => 'Kedisiplinan']]],
        ['label' => 'Aspek Non-Teknis', 'items' => [['label' => 'Kerja sama']]],
    ], $kaprog);
}

test('publish form tanpa item ditolak', function () {
    ['kaprog' => $kaprog, 'period' => $period] = pklFormContext();

    $definition = app(CreateFormDefinition::class)->handle($period, 'sidang', 'Form Kosong', $kaprog);
    $definition = app(SaveFormStructure::class)->handle(
        $definition,
        [['label' => 'Aspek Teknis', 'items' => []]],
        $kaprog,
    );

    expect(fn () => app(PublishFormDefinition::class)->handle($definition, $kaprog))
        ->toThrow(ValidationException::class);

    expect($definition->refresh()->is_published)->toBeFalse();
});

test('publish form berisi item berhasil dan idempoten', function () {
    ['kaprog' => $kaprog, 'period' => $period] = pklFormContext();
    $definition = pklFormDefinition($kaprog, $period);

    $published = app(PublishFormDefinition::class)->handle($definition, $kaprog);
    $again = app(PublishFormDefinition::class)->handle($published->refresh(), $kaprog);

    expect($published->is_published)->toBeTrue()
        ->and($published->published_at)->not->toBeNull()
        ->and($again->is_published)->toBeTrue()
        ->and($again->items()->count())->toBe(3);
});

test('struktur terkunci setelah ada submission dan versi baru dibuat', function () {
    ['kaprog' => $kaprog, 'period' => $period] = pklFormContext();
    $definition = pklFormDefinition($kaprog, $period, FormDefinition::TYPE_INDUSTRI, catatan: false);
    $company = Company::factory()->create();

    FormSubmission::query()->create([
        'form_definition_id' => $definition->getKey(),
        'form_type' => FormDefinition::TYPE_INDUSTRI,
        'company_id' => $company->getKey(),
        'academic_period_id' => $period->getKey(),
        'jawaban' => ['items' => [], 'nilai_total' => 0],
    ]);

    expect($definition->refresh()->isLocked())->toBeTrue();

    expect(fn () => app(SaveFormStructure::class)->handle(
        $definition,
        [['label' => 'Diubah', 'items' => [['label' => 'Item baru']]]],
        $kaprog,
    ))->toThrow(ValidationException::class);

    expect(FormDefinition::query()->whereKey($definition->getKey())->value('nama'))->toBe($definition->nama);

    $copy = app(DuplicateFormDefinition::class)->handle($definition->refresh(), $kaprog);

    expect($copy->getKey())->not->toBe($definition->getKey())
        ->and($copy->is_published)->toBeFalse()
        ->and($copy->items()->count())->toBe($definition->items()->count())
        ->and($copy->submissions()->count())->toBe(0);
});

test('kaprog dari jurusan lain tidak dapat mengubah form', function () {
    ['kaprog' => $kaprog, 'period' => $period] = pklFormContext();
    $definition = pklFormDefinition($kaprog, $period);
    $other = pklUser(['kaprog']);

    session(['active_role' => 'kaprog']);

    expect(fn () => app(SaveFormStructure::class)->handle(
        $definition,
        [['label' => 'Aspek', 'items' => [['label' => 'Item']]]],
        $other,
    ))->toThrow(AuthorizationException::class);

    expect(fn () => app(PublishFormDefinition::class)->handle($definition, $other))
        ->toThrow(AuthorizationException::class);
});

test('nonaktifkan form lain tidak mengubah jumlah item form yang sudah dipublikasikan', function () {
    ['kaprog' => $kaprog, 'period' => $period] = pklFormContext();
    $sidang = pklFormDefinition($kaprog, $period, FormDefinition::TYPE_SIDANG);
    $industri = pklFormDefinition($kaprog, $period, FormDefinition::TYPE_INDUSTRI);

    app(PublishFormDefinition::class)->handle($sidang, $kaprog);

    expect($sidang->refresh()->is_published)->toBeTrue()
        ->and($industri->refresh()->is_published)->toBeFalse()
        ->and($sidang->items()->count())->toBe(3)
        ->and($industri->items()->count())->toBe(3);
});

test('halaman builder menyimpan lalu mempublikasikan form', function () {
    ['kaprog' => $kaprog, 'period' => $period] = pklFormContext();
    Filament::setCurrentPanel(Filament::getPanel('kaprog'));
    $this->actingAs($kaprog)->withSession(['active_role' => 'kaprog']);

    Livewire::test(FormBuilder::class)
        ->callAction('new', data: [
            'periodId' => $period->getKey(),
            'nama' => 'Form Sidang RPL',
            'formType' => FormDefinition::TYPE_SIDANG,
            'hasKehadiran' => true,
        ])
        ->assertHasNoFormErrors();

    $definition = FormDefinition::query()->where('nama', 'Form Sidang RPL')->firstOrFail();

    Livewire::test(FormBuilder::class)
        ->callAction(TestAction::make('open')->table($definition))
        ->fillForm([
            'sections' => [
                ['label' => 'Aspek Teknis', 'items' => [['label' => 'Penguasaan materi']]],
            ],
        ])
        ->callAction('save')
        ->assertHasNoFormErrors();

    expect($definition->refresh()->items()->count())->toBe(1);

    Livewire::test(FormBuilder::class)
        ->callAction(TestAction::make('open')->table($definition))
        ->callAction('publish')
        ->assertHasNoFormErrors();

    expect($definition->refresh()->is_published)->toBeTrue()
        ->and($definition->has_kehadiran)->toBeTrue()
        ->and($definition->items()->count())->toBe(1);
});
