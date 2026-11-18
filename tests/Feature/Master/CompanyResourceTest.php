<?php

use App\Filament\Hubin\Resources\Companies\Pages\ManageCompanies;
use App\Models\Company;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('hubin'));

    $this->actingAs(pklUser(['hubin']))->withSession(['active_role' => 'hubin']);
});

test('hubin dapat membuka daftar perusahaan', function () {
    Company::factory()->create(['nama' => 'PT Contoh']);

    $this->get('/hubin/companies')
        ->assertOk()
        ->assertSee('PT Contoh');
});

test('perusahaan dengan email duplikat setelah normalisasi ditolak lewat form', function () {
    Company::factory()->create(['nama' => 'PT Pertama', 'email' => 'info@pt-test.co.id']);

    Livewire\Livewire::test(ManageCompanies::class)
        ->callAction('create', data: [
            'nama' => 'PT Kedua',
            'alamat' => 'Jl. Baru 1',
            'email' => '  INFO@PT-Test.co.id ',
        ])
        ->assertHasFormErrors(['email']);

    expect(Company::query()->where('nama', 'PT Kedua')->exists())->toBeFalse();
});

test('perusahaan baru yang sah tersimpan dengan kontak ternormalisasi', function () {
    Livewire\Livewire::test(ManageCompanies::class)
        ->callAction('create', data: [
            'nama' => 'PT Baharu',
            'alamat' => 'Jl. Baharu 2',
            'email' => ' Info@Baharu.Co.Id ',
            'website' => 'https://WWW.Baharu.co.id/',
            'telp_fax' => '+62 21-555-9000',
        ])
        ->assertHasNoFormErrors();

    $company = Company::query()->where('nama', 'PT Baharu')->firstOrFail();

    expect($company->email)->toBe('info@baharu.co.id')
        ->and($company->website)->toBe('baharu.co.id')
        ->and($company->telp_fax)->toBe('0215559000');
});

test('jabatan generik ditolak sebagai nama signer', function () {
    Livewire\Livewire::test(ManageCompanies::class)
        ->callAction('create', data: [
            'nama' => 'PT Signer',
            'alamat' => 'Jl. Signer 3',
            'pembimbing_industri_nama' => 'HRD',
        ])
        ->assertHasFormErrors(['pembimbing_industri_nama']);
});

test('action gabungkan memindahkan referensi ke perusahaan tujuan', function () {
    $source = Company::factory()->create(['nama' => 'PT Sumber']);
    $target = Company::factory()->create(['nama' => 'PT Tujuan']);

    Livewire\Livewire::test(ManageCompanies::class)
        ->callAction(TestAction::make('merge')->table($source), data: ['target_id' => $target->id])
        ->assertHasNoFormErrors();

    expect($source->refresh()->merged_into_id)->toBe($target->id);
});

test('action gabungkan tidak muncul untuk perusahaan yang sudah digabung', function () {
    $target = Company::factory()->create();
    $source = Company::factory()->create(['merged_into_id' => $target->id]);

    Livewire\Livewire::test(ManageCompanies::class)
        ->assertActionHidden(TestAction::make('merge')->table($source));
});
