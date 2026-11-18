<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Satu baris impor tidak dapat diproses (T2.1/T2.2). Pesannya Bahasa Indonesia dan
 * hanya menyebut baris itu, karena impor memakai transaksi per baris: baris gagal
 * masuk laporan, baris lain tetap diproses (PRD F2 edge case).
 */
class ImportRowException extends RuntimeException
{
    public static function missing(string $column): self
    {
        return new self("Kolom \"{$column}\" wajib diisi.");
    }

    public static function unknown(string $label, string $value): self
    {
        return new self("{$label} \"{$value}\" tidak ditemukan.");
    }

    public static function invalid(string $message): self
    {
        return new self($message);
    }
}
