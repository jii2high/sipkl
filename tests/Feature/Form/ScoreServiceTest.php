<?php

use App\Actions\Form\CreateFormDefinition;
use App\Actions\Form\SaveFormStructure;
use App\Models\AcademicPeriod;
use App\Models\FormDefinition;
use App\Services\ScoreService;
use Illuminate\Validation\ValidationException;

/** @return array{definition: FormDefinition, itemIds: array<int, int>} */
function pklScoreDefinition(bool $kehadiran = true, bool $catatan = true): array
{
    $kaprog = pklUser(['kaprog']);
    session(['active_role' => 'kaprog']);
    $period = AcademicPeriod::factory()->create();

    $definition = app(CreateFormDefinition::class)->handle(
        $period,
        FormDefinition::TYPE_SIDANG,
        'Form Sidang',
        $kaprog,
        $kehadiran,
        $catatan,
    );

    $definition = app(SaveFormStructure::class)->handle($definition, [
        ['label' => 'Aspek Teknis', 'items' => [['label' => 'A'], ['label' => 'B']]],
        ['label' => 'Aspek Non-Teknis', 'items' => [['label' => 'C']]],
    ], $kaprog);

    return ['definition' => $definition, 'itemIds' => $definition->items()->pluck('form_items.id')->all()];
}

test('nilai total adalah rata-rata item dibulatkan dua desimal', function () {
    ['definition' => $definition, 'itemIds' => $ids] = pklScoreDefinition();

    $jawaban = app(ScoreService::class)->normalize($definition, [
        'items' => [$ids[0] => 90, $ids[1] => 80, $ids[2] => 75],
        'kehadiran' => ['sakit' => 1, 'izin' => 0, 'tanpa_keterangan' => 2],
        'catatan' => 'Perlu latihan presentasi.',
    ]);

    expect($jawaban['nilai_total'])->toBe(81.67)
        ->and($jawaban['items'][$ids[0]]['nilai'])->toBe(90.0)
        ->and($jawaban['kehadiran'])->toBe(['sakit' => 1, 'izin' => 0, 'tanpa_keterangan' => 2])
        ->and($jawaban['catatan'])->toBe('Perlu latihan presentasi.');
});

test('batas nilai 0 dan 100 diterima', function () {
    ['definition' => $definition, 'itemIds' => $ids] = pklScoreDefinition();

    $jawaban = app(ScoreService::class)->normalize($definition, [
        'items' => [$ids[0] => 0, $ids[1] => 100, $ids[2] => 50],
    ]);

    expect($jawaban['nilai_total'])->toBe(50.0);
});

test('nilai di luar rentang dan item kosong ditolak', function () {
    ['definition' => $definition, 'itemIds' => $ids] = pklScoreDefinition();

    expect(fn () => app(ScoreService::class)->normalize($definition, [
        'items' => [$ids[0] => 101, $ids[1] => 80, $ids[2] => 70],
    ]))->toThrow(ValidationException::class);

    expect(fn () => app(ScoreService::class)->normalize($definition, [
        'items' => [$ids[0] => -1, $ids[1] => 80, $ids[2] => 70],
    ]))->toThrow(ValidationException::class);

    expect(fn () => app(ScoreService::class)->normalize($definition, [
        'items' => [$ids[0] => 90, $ids[1] => 80],
    ]))->toThrow(ValidationException::class);

    expect(fn () => app(ScoreService::class)->normalize($definition, [
        'items' => [$ids[0] => 90, $ids[1] => 80, $ids[2] => 70, 999999 => 50],
    ]))->toThrow(ValidationException::class);
});

test('section kehadiran dan catatan tidak memengaruhi nilai total', function () {
    ['definition' => $definition, 'itemIds' => $ids] = pklScoreDefinition();

    $withoutExtras = app(ScoreService::class)->normalize($definition, [
        'items' => [$ids[0] => 90, $ids[1] => 80, $ids[2] => 70],
    ]);

    $withExtras = app(ScoreService::class)->normalize($definition, [
        'items' => [$ids[0] => 90, $ids[1] => 80, $ids[2] => 70],
        'kehadiran' => ['sakit' => 99, 'izin' => 99, 'tanpa_keterangan' => 99],
        'catatan' => 'Sakit sebulan penuh.',
    ]);

    expect($withExtras['nilai_total'])->toBe($withoutExtras['nilai_total'])
        ->and($withoutExtras['kehadiran'])->toBe(['sakit' => 0, 'izin' => 0, 'tanpa_keterangan' => 0])
        ->and($withoutExtras['catatan'])->toBe('');
});
