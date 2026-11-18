<?php

use App\Models\AcademicPeriod;
use App\Models\Company;
use App\Models\PengajuanPkl;
use App\Policies\AcademicPeriodPolicy;

test('periode tanpa data boleh dihapus oleh Hubin', function () {
    $hubin = pklUser(['hubin']);
    session(['active_role' => 'hubin']);
    $period = AcademicPeriod::factory()->create();

    expect((new AcademicPeriodPolicy)->delete($hubin, $period))->toBeTrue();
});

test('periode berdata tidak boleh dihapus', function () {
    $hubin = pklUser(['hubin']);
    session(['active_role' => 'hubin']);
    $period = AcademicPeriod::factory()->create();

    PengajuanPkl::query()->create([
        'company_id' => Company::factory()->create()->id,
        'academic_period_id' => $period->id,
    ]);

    expect((new AcademicPeriodPolicy)->delete($hubin, $period))->toBeFalse();
});

test('peran selain Hubin tidak boleh mengubah periode', function () {
    $guru = pklUser(['guru_pembimbing']);
    session(['active_role' => 'guru_pembimbing']);
    $period = AcademicPeriod::factory()->create();

    expect((new AcademicPeriodPolicy)->update($guru, $period))->toBeFalse()
        ->and((new AcademicPeriodPolicy)->delete($guru, $period))->toBeFalse();
});
