<?php

use App\Filament\Hubin\Resources\SignatureAssets\Pages\ManageSignatureAssets;
use App\Models\SignatureAsset;
use Filament\Facades\Filament;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('hubin'));
    Storage::fake('local');

    $this->actingAs(pklUser(['hubin']))->withSession(['active_role' => 'hubin']);
});

test('hubin dapat menyimpan satu scan TTD Kepsek aktif lewat halaman', function () {
    pklUser(['kepsek']);

    Livewire\Livewire::test(ManageSignatureAssets::class)
        ->callAction('upload', data: ['file' => UploadedFile::fake()->image('ttd.png')])
        ->assertHasNoFormErrors();

    expect(SignatureAsset::query()->where('is_active', true)->count())->toBe(1);
});

test('unggahan kedua menonaktifkan scan sebelumnya', function () {
    pklUser(['kepsek']);

    Livewire\Livewire::test(ManageSignatureAssets::class)
        ->callAction('upload', data: ['file' => UploadedFile::fake()->image('ttd1.png')]);

    Livewire\Livewire::test(ManageSignatureAssets::class)
        ->callAction('upload', data: ['file' => UploadedFile::fake()->image('ttd2.png')]);

    expect(SignatureAsset::query()->where('is_active', true)->count())->toBe(1)
        ->and(SignatureAsset::query()->count())->toBe(2);
});
