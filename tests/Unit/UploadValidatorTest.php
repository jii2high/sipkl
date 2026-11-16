<?php

use App\Services\UploadValidator;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

uses(TestCase::class);

function pdfContent(): string
{
    return "%PDF-1.4\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj\n"
        ."2 0 obj<</Type/Pages/Count 0/Kids[]>>endobj\n"
        ."trailer<</Root 1 0 R>>\n%%EOF";
}

function pngContent(): string
{
    return base64_decode(
        'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='
    );
}

it('menerima pdf dengan isi yang benar dan mengembalikan ekstensi', function () {
    $file = UploadedFile::fake()->createWithContent('laporan.pdf', pdfContent());

    expect(app(UploadValidator::class)->validate($file, 'laporan'))->toBe('pdf');
});

it('menolak file .pdf yang isinya gambar', function () {
    $file = UploadedFile::fake()->createWithContent('laporan.pdf', pngContent());

    expect(fn () => app(UploadValidator::class)->validate($file, 'laporan'))
        ->toThrow(ValidationException::class);
});

it('menolak file yang melebihi batas ukuran profil', function () {
    $file = UploadedFile::fake()->create('foto.png', 6000);

    expect(fn () => app(UploadValidator::class)->validate($file, 'foto'))
        ->toThrow(ValidationException::class);
});

it('menolak ekstensi di luar profil', function () {
    $file = UploadedFile::fake()->createWithContent('skrip.exe', pdfContent());

    expect(fn () => app(UploadValidator::class)->validate($file, 'laporan'))
        ->toThrow(ValidationException::class);
});

it('menolak file kosong', function () {
    $file = UploadedFile::fake()->createWithContent('kosong.pdf', '');

    expect(fn () => app(UploadValidator::class)->validate($file, 'laporan'))
        ->toThrow(ValidationException::class);
});

it('menolak profil upload yang tidak dikenal', function () {
    $file = UploadedFile::fake()->createWithContent('laporan.pdf', pdfContent());

    expect(fn () => app(UploadValidator::class)->validate($file, 'video'))
        ->toThrow(InvalidArgumentException::class);
});
