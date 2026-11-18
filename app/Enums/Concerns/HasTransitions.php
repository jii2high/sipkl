<?php

namespace App\Enums\Concerns;

/**
 * Dipakai Enum state machine untuk memeriksa transisi lewat tabel transisi
 * yang sama. Lihat App\Enums\Contracts\StateEnum.
 */
trait HasTransitions
{
    public function canTransitionTo(self $to, ?string $actor = null): bool
    {
        return static::transitions()->allows($this->value, $to->value, $actor);
    }

    public function assertTransitionTo(self $to, ?string $actor = null): void
    {
        static::transitions()->assert($this->value, $to->value, $actor);
    }
}
