<?php

use App\Livewire\Support\CompanyAutocomplete;
use App\Models\Company;

test('mencari dan memilih perusahaan mengirim event dengan kontak ter-mask', function () {
    $company = Company::factory()->create([
        'nama' => 'PT Cari Saya',
        'alamat' => 'Jl. Pencarian 5',
        'cp_phone' => '081234567890',
    ]);
    Company::factory()->create(['nama' => 'PT Lain']);

    Livewire\Livewire::test(CompanyAutocomplete::class)
        ->set('search', 'Cari')
        ->assertSee('PT Cari Saya')
        ->assertDontSee('PT Lain')
        ->assertSee('0812******90')
        ->call('select', $company->id)
        ->assertSet('selectedId', $company->id)
        ->assertSet('selectedName', 'PT Cari Saya')
        ->assertDispatched('company-selected', id: $company->id, nama: 'PT Cari Saya');
});

test('kata kunci kurang dari dua huruf tidak mencari', function () {
    Company::factory()->create(['nama' => 'PT Contoh']);

    Livewire\Livewire::test(CompanyAutocomplete::class)
        ->set('search', 'P')
        ->assertDontSee('PT Contoh');
});

test('perusahaan hasil merge tidak muncul sebagai kandidat', function () {
    $target = Company::factory()->create(['nama' => 'PT Tujuan']);
    Company::factory()->create(['nama' => 'PT Gabungan', 'merged_into_id' => $target->id]);

    Livewire\Livewire::test(CompanyAutocomplete::class)
        ->set('search', 'Gabungan')
        ->assertDontSee('PT Gabungan');
});
