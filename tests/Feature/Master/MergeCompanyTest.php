<?php

use App\Actions\Master\MergeCompany;
use App\Exceptions\CompanyMergeException;
use App\Models\AcademicPeriod;
use App\Models\Company;
use App\Models\GuruPembimbingPlotting;
use App\Models\PengajuanPkl;
use App\Models\PengajuanSiswa;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * @return array{0: Company, 1: Company, 2: PengajuanPkl, 3: PengajuanSiswa, 4: GuruPembimbingPlotting, 5: User}
 */
function seedMergeReferences(): array
{
    $hubin = pklUser(['hubin']);
    $guru = pklUser(['guru_pembimbing']);
    $siswa = pklUser(['siswa']);
    $period = AcademicPeriod::factory()->create();

    $source = Company::factory()->create();
    $target = Company::factory()->create();

    $group = PengajuanPkl::query()->create([
        'company_id' => $source->id,
        'academic_period_id' => $period->id,
    ]);

    $pengajuan = PengajuanSiswa::query()->create([
        'pengajuan_pkl_id' => $group->id,
        'company_id' => $source->id,
        'academic_period_id' => $period->id,
        'siswa_id' => $siswa->id,
        'status' => 'draft',
    ]);

    $plotting = GuruPembimbingPlotting::query()->create([
        'company_id' => $source->id,
        'academic_period_id' => $period->id,
        'guru_id' => $guru->id,
        'plotted_by' => $hubin->id,
    ]);

    return [$source, $target, $group, $pengajuan, $plotting, $hubin];
}

test('merge memindahkan seluruh referensi ke perusahaan tujuan', function () {
    [$source, $target, $group, $pengajuan, $plotting, $hubin] = seedMergeReferences();

    app(MergeCompany::class)->handle($source, $target, $hubin);

    expect($source->refresh()->merged_into_id)->toBe($target->id)
        ->and($group->refresh()->company_id)->toBe($target->id)
        ->and($pengajuan->refresh()->company_id)->toBe($target->id)
        ->and($plotting->refresh()->company_id)->toBe($target->id);
});

test('merge menulis satu entri audit', function () {
    [$source, $target, , , , $hubin] = seedMergeReferences();

    app(MergeCompany::class)->handle($source, $target, $hubin);

    $entries = DB::table('activity_log')->where('event', 'merged')->get();

    expect($entries)->toHaveCount(1)
        ->and($entries->first()->subject_id)->toBe($source->id);
});

test('merge ke diri sendiri ditolak', function () {
    [$source, , , , , $hubin] = seedMergeReferences();

    expect(fn () => app(MergeCompany::class)->handle($source, $source, $hubin))
        ->toThrow(CompanyMergeException::class);

    expect($source->refresh()->merged_into_id)->toBeNull();
});

test('perusahaan yang sudah digabung tidak dapat digabung lagi', function () {
    [$source, $target, , , , $hubin] = seedMergeReferences();
    $third = Company::factory()->create();

    app(MergeCompany::class)->handle($source, $target, $hubin);

    expect(fn () => app(MergeCompany::class)->handle($source->refresh(), $third, $hubin))
        ->toThrow(CompanyMergeException::class);
});

test('merge ke perusahaan yang sudah digabung ditolak', function () {
    [$source, $target, , , , $hubin] = seedMergeReferences();
    $third = Company::factory()->create();

    app(MergeCompany::class)->handle($source, $target, $hubin);

    expect(fn () => app(MergeCompany::class)->handle($third, $source->refresh(), $hubin))
        ->toThrow(CompanyMergeException::class);

    expect($third->refresh()->merged_into_id)->toBeNull();
});
