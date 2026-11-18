<?php

use App\Actions\Master\UploadKepsekSignature;
use App\Models\SignatureAsset;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

test('unggah scan TTD Kepsek menyimpan berkas privat dan menyisakan satu aktif', function () {
    Storage::fake('local');
    $hubin = pklUser(['hubin']);
    pklUser(['kepsek']);

    $first = app(UploadKepsekSignature::class)->handle(UploadedFile::fake()->image('ttd1.png'), $hubin);
    $second = app(UploadKepsekSignature::class)->handle(UploadedFile::fake()->image('ttd2.png'), $hubin);

    expect($first->refresh()->is_active)->toBeFalse()
        ->and($second->is_active)->toBeTrue()
        ->and(SignatureAsset::query()->where('is_active', true)->count())->toBe(1);

    Storage::disk('local')->assertExists($second->file_path);
});

test('berkas bukan gambar atau pdf ditolak', function () {
    Storage::fake('local');
    $hubin = pklUser(['hubin']);
    pklUser(['kepsek']);

    expect(fn () => app(UploadKepsekSignature::class)->handle(
        UploadedFile::fake()->create('ttd.exe', 1, 'application/octet-stream'),
        $hubin,
    ))->toThrow(ValidationException::class);

    expect(SignatureAsset::query()->count())->toBe(0);
});

test('unggahan tanpa akun Kepsek ditolak', function () {
    Storage::fake('local');
    $hubin = pklUser(['hubin']);

    expect(fn () => app(UploadKepsekSignature::class)->handle(
        UploadedFile::fake()->image('ttd.png'),
        $hubin,
    ))->toThrow(RuntimeException::class);
});
