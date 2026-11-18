<?php

use App\Services\FileStorageService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

const PKL_MINIMAL_PDF = "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\ntrailer\n<< /Root 1 0 R >>\n%%EOF";

test('berkas sah disimpan privat dengan nama acak', function () {
    Storage::fake('local');

    $file = UploadedFile::fake()->createWithContent('laporan.pdf', PKL_MINIMAL_PDF);
    $path = app(FileStorageService::class)->store($file, 'bimbingan');

    expect($path)->toStartWith('bimbingan/')
        ->and($path)->not->toContain('laporan')
        ->and(Storage::disk('local')->exists($path))->toBeTrue();
});

test('berkas berekstensi palsu ditolak karena isinya bukan jenis yang diizinkan', function () {
    Storage::fake('local');

    $file = UploadedFile::fake()->createWithContent('dokumentasi.png', PKL_MINIMAL_PDF);

    expect(fn () => app(FileStorageService::class)->store($file, 'visitasi'))
        ->toThrow(ValidationException::class);

    expect(Storage::disk('local')->allFiles())->toBeEmpty();
});

test('isi skrip yang dinamai pdf ditolak', function () {
    Storage::fake('local');

    $file = UploadedFile::fake()->createWithContent('laporan.pdf', "<?php echo 'halo'; ?>");

    expect(fn () => app(FileStorageService::class)->store($file, 'bimbingan'))
        ->toThrow(ValidationException::class);
});

test('berkas melebihi batas ukuran ditolak', function () {
    Storage::fake('local');

    $file = UploadedFile::fake()->create('laporan.pdf', 11 * 1024);

    expect(fn () => app(FileStorageService::class)->store($file, 'bimbingan'))
        ->toThrow(ValidationException::class);
});

test('jenis unggahan tak dikenal ditolak', function () {
    Storage::fake('local');

    expect(fn () => app(FileStorageService::class)->store(
        UploadedFile::fake()->createWithContent('laporan.pdf', PKL_MINIMAL_PDF),
        'tidak_ada',
    ))->toThrow(RuntimeException::class);
});
