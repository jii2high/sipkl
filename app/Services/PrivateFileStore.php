<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Penyimpanan dokumen privat (PD-09). Pola path: {period}/{profil}/{uuid}.{ext}.
 *
 * Tidak ada URL publik: unduhan hanya lewat PrivateFileController.
 */
final class PrivateFileStore
{
    public const DISK = 'private';

    public function __construct(private readonly UploadValidator $validator) {}

    /**
     * Validasi lalu simpan file; mengembalikan path relatif di disk `private`.
     */
    public function put(UploadedFile $file, string $profile, int $academicPeriodId): string
    {
        $extension = $this->validator->validate($file, $profile);
        $directory = $academicPeriodId.'/'.$profile;
        $name = Str::uuid()->toString().'.'.$extension;

        $path = Storage::disk(self::DISK)->putFileAs($directory, $file, $name);

        if (! is_string($path)) {
            throw new RuntimeException('Gagal menyimpan file ke disk private.');
        }

        return $path;
    }

    public function exists(string $path): bool
    {
        return $this->isSafePath($path) && Storage::disk(self::DISK)->exists($path);
    }

    public function delete(string $path): bool
    {
        if (! $this->isSafePath($path)) {
            return false;
        }

        return Storage::disk(self::DISK)->delete($path);
    }

    /**
     * Cegah path traversal dan path di luar pola yang kita tulis sendiri.
     */
    public function isSafePath(string $path): bool
    {
        if ($path === '' || str_contains($path, '..') || str_starts_with($path, '/')) {
            return false;
        }

        return preg_match('#^[0-9]+/[a-z_]+/[A-Za-z0-9-]+\.[A-Za-z0-9]+$#', $path) === 1;
    }

    public function download(string $path): StreamedResponse
    {
        return Storage::disk(self::DISK)->download($path);
    }
}
