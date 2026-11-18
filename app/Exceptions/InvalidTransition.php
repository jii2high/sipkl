<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Transisi status yang tidak diizinkan tabel transisi (TRD.md §5.1). Dipetakan ke
 * 409 Conflict (bentrok status) atau 422 (permintaan tidak dapat diproses) dengan
 * pesan Bahasa Indonesia; lihat bootstrap/app.php.
 */
class InvalidTransition extends RuntimeException
{
    public function __construct(string $message, private readonly int $status = 409)
    {
        parent::__construct($message);
    }

    public function httpStatus(): int
    {
        return $this->status;
    }

    public static function between(string $from, string $to, ?string $actor = null): self
    {
        $actorText = $actor === null ? 'peran ini' : "peran {$actor}";

        return new self("Status tidak dapat berpindah dari \"{$from}\" ke \"{$to}\" oleh {$actorText}.");
    }

    public static function unexpected(string $current, string $expected): self
    {
        return new self("Status saat ini \"{$current}\" tidak sesuai. Dibutuhkan status \"{$expected}\".");
    }

    public static function unknownPair(string $from, string $to): self
    {
        return new self("Pasangan transisi \"{$from}\" ke \"{$to}\" tidak dikenal.", 422);
    }
}
