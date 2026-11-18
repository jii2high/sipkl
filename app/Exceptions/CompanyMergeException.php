<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Penolakan merge perusahaan (BR-F2-4, PRD edge case "merge ke diri sendiri
 * ditolak"). Dipetakan ke 422 dengan pesan Bahasa Indonesia oleh bootstrap/app.php.
 */
class CompanyMergeException extends RuntimeException
{
    public function __construct(string $message, private readonly int $status = 422)
    {
        parent::__construct($message);
    }

    public function httpStatus(): int
    {
        return $this->status;
    }

    public static function selfMerge(): self
    {
        return new self('Perusahaan tidak dapat digabung dengan dirinya sendiri.');
    }

    public static function missingTarget(): self
    {
        return new self('Perusahaan tujuan merge tidak sah.');
    }

    public static function alreadyMerged(string $nama): self
    {
        return new self("Perusahaan \"{$nama}\" sudah pernah digabung dan tidak dapat digabung lagi.");
    }

    public static function targetMerged(string $nama): self
    {
        return new self("Perusahaan tujuan \"{$nama}\" adalah hasil gabungan; pilih perusahaan yang masih aktif.");
    }

    public static function conflictingReference(string $table): self
    {
        return new self("Merge gagal: ada data pada {$table} yang akan menjadi duplikat. Rapikan data itu lebih dulu.");
    }
}
