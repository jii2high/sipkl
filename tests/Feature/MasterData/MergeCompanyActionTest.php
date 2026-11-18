<?php

use App\Actions\Company\MergeCompanyAction;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

function gabungPerusahaan(Company $sumber, Company $target, ?User $oleh = null, ?string $alasan = null): Company
{
    return app(MergeCompanyAction::class)->execute($sumber, $target, $oleh, $alasan);
}

it('menolak merge perusahaan ke dirinya sendiri', function () {
    $company = Company::factory()->create();

    expect(fn () => gabungPerusahaan($company, $company))
        ->toThrow(ValidationException::class);

    expect($company->fresh()->merged_into_id)->toBeNull();
});

it('mengarahkan sumber ke target, memindahkan referensi, dan mencatat audit', function () {
    $hubin = User::factory()->create();
    $target = Company::factory()->create(['nama' => 'PT Tujuan']);
    $sumber = Company::factory()->create([
        'nama' => 'PT Sumber',
        'email' => 'hrd@sumber.co.id',
        'website' => 'sumber.co.id',
        'telp_fax' => '0215550001',
    ]);
    $anak = Company::factory()->sudahMergeKe($sumber)->create();

    $hasil = gabungPerusahaan($sumber, $target, $hubin, 'Duplikat nama dan alamat');

    $sumber->refresh();
    $anak->refresh();

    expect($hasil->id)->toBe($target->id)
        ->and($sumber->merged_into_id)->toBe($target->id)
        ->and($sumber->email)->toBeNull()
        ->and($sumber->website)->toBeNull()
        ->and($sumber->telp_fax)->toBeNull()
        ->and($anak->merged_into_id)->toBe($target->id);

    $log = DB::table('activity_log')->where('description', 'company.merged')->sole();

    expect($log->subject_id)->toBe($sumber->id)
        ->and($log->causer_id)->toBe($hubin->id)
        ->and(json_decode($log->properties, true))
        ->toMatchArray([
            'sumber_id' => $sumber->id,
            'target_id' => $target->id,
            'alasan' => 'Duplikat nama dan alamat',
        ])
        ->and(json_decode($log->properties, true)['referensi']['companies']['merged_into_id'])->toBe(1);
});

it('membebaskan email/website/telp sumber agar bisa dipakai perusahaan lain', function () {
    $target = Company::factory()->create();
    $sumber = Company::factory()->create([
        'email' => 'hrd@contoh.co.id',
        'website' => 'contoh.co.id',
        'telp_fax' => '0215550001',
    ]);

    gabungPerusahaan($sumber, $target);

    $baru = Company::factory()->create([
        'email' => 'hrd@contoh.co.id',
        'website' => 'contoh.co.id',
        'telp_fax' => '0215550001',
    ]);

    expect($baru->exists)->toBeTrue();
});

it('menolak sumber yang sudah pernah digabungkan', function () {
    $target = Company::factory()->create();
    $sumber = Company::factory()->sudahMergeKe($target)->create();
    $lain = Company::factory()->create();

    expect(fn () => gabungPerusahaan($sumber, $lain))
        ->toThrow(ValidationException::class);
});

it('menolak target yang sudah digabungkan ke perusahaan lain', function () {
    $utama = Company::factory()->create();
    $target = Company::factory()->sudahMergeKe($utama)->create();
    $sumber = Company::factory()->create();

    expect(fn () => gabungPerusahaan($sumber, $target))
        ->toThrow(ValidationException::class);

    expect($sumber->fresh()->merged_into_id)->toBeNull();
});

it('tidak mengubah kolom perusahaan target', function () {
    $target = Company::factory()->create([
        'nama' => 'PT Tujuan',
        'email' => 'hrd@tujuan.co.id',
    ]);
    $sumber = Company::factory()->create();

    gabungPerusahaan($sumber, $target);

    expect($target->fresh()->email)->toBe('hrd@tujuan.co.id')
        ->and($target->fresh()->nama)->toBe('PT Tujuan');
});
