<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

/**
 * Satu jalur validasi upload (PRD §1.5 + PD-09, TASKS M0.10).
 *
 * Ekstensi DAN MIME hasil deteksi konten harus cocok dengan profil; file kosong,
 * terlalu besar, atau MIME tidak sesuai ekstensi ditolak.
 */
final class UploadValidator
{
    /**
     * @var array<string, array{extensions: list<string>, max_bytes: int}>
     */
    private const PROFILES = [
        'laporan' => ['extensions' => ['pdf', 'doc', 'docx'], 'max_bytes' => 10 * 1024 * 1024],
        'foto' => ['extensions' => ['jpg', 'jpeg', 'png'], 'max_bytes' => 5 * 1024 * 1024],
        'balasan' => ['extensions' => ['pdf', 'jpg', 'jpeg', 'png'], 'max_bytes' => 10 * 1024 * 1024],
    ];

    /**
     * MIME yang sah untuk tiap ekstensi (hasil deteksi konten, bukan klaim klien).
     *
     * docx dideteksi sebagian alat sebagai application/zip (container OOXML).
     *
     * @var array<string, list<string>>
     */
    private const MIME_BY_EXTENSION = [
        'pdf' => ['application/pdf'],
        'doc' => ['application/msword', 'application/vnd.ms-office', 'application/x-ole-storage'],
        'docx' => [
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/zip',
        ],
        'jpg' => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'png' => ['image/png'],
    ];

    /**
     * Validasi file dan kembalikan ekstensi ternormalisasi (huruf kecil).
     *
     * @throws ValidationException
     */
    public function validate(UploadedFile $file, string $profile): string
    {
        $config = self::PROFILES[$profile] ?? throw new \InvalidArgumentException("Profil upload [{$profile}] tidak dikenal.");

        if (! $file->isValid()) {
            $this->fail('File gagal diunggah. Silakan coba lagi.');
        }

        $size = (int) $file->getSize();

        if ($size <= 0) {
            $this->fail('File kosong tidak dapat diunggah.');
        }

        if ($size > $config['max_bytes']) {
            $this->fail(sprintf(
                'Ukuran file melebihi batas %d MB.',
                intdiv($config['max_bytes'], 1024 * 1024),
            ));
        }

        $extension = strtolower($file->getClientOriginalExtension());

        if ($extension === '' || ! in_array($extension, $config['extensions'], true)) {
            $this->fail(sprintf(
                'Ekstensi file harus salah satu dari: %s.',
                implode(', ', $config['extensions']),
            ));
        }

        $detected = $this->detectMimeType($file);

        if ($detected === null) {
            $this->fail('Jenis file tidak dapat dideteksi.');
        }

        if (! in_array($detected, self::MIME_BY_EXTENSION[$extension] ?? [], true)) {
            $this->fail(sprintf(
                'Isi file tidak cocok dengan ekstensi .%s (terdeteksi %s).',
                $extension,
                $detected,
            ));
        }

        return $extension;
    }

    /**
     * @return list<string>
     */
    public function allowedExtensions(string $profile): array
    {
        return self::PROFILES[$profile]['extensions'] ?? throw new \InvalidArgumentException("Profil upload [{$profile}] tidak dikenal.");
    }

    /**
     * Deteksi MIME dari isi file (bukan klaim klien / ekstensi nama file).
     */
    private function detectMimeType(UploadedFile $file): ?string
    {
        $path = $file->getRealPath();

        if (! is_string($path) || ! is_file($path)) {
            return null;
        }

        $detected = (new \finfo(FILEINFO_MIME_TYPE))->file($path);

        return is_string($detected) && $detected !== '' ? strtolower($detected) : null;
    }

    /**
     * @throws ValidationException
     */
    private function fail(string $message): never
    {
        throw ValidationException::withMessages(['file' => $message]);
    }
}
