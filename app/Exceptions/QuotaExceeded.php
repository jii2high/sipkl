<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Kuota `sedang_pkl` per company+periode sudah penuh (BR-F3-3). Dilempar dari
 * dalam transaksi setelah baris `companies` dikunci, sehingga tidak ada efek
 * samping; dipetakan ke 409 oleh bootstrap/app.php.
 */
class QuotaExceeded extends RuntimeException
{
    public function __construct(string $message, private readonly int $status = 409)
    {
        parent::__construct($message);
    }

    public function httpStatus(): int
    {
        return $this->status;
    }

    public static function forCompany(string $nama, int $maks): self
    {
        return new self(__('pkl.quota.full', ['nama' => $nama, 'maks' => $maks]));
    }
}
