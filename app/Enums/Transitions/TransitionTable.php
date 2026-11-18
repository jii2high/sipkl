<?php

namespace App\Enums\Transitions;

use App\Exceptions\InvalidTransition;

/**
 * Tabel transisi `dari -> ke -> aktor yang diizinkan` (TRD.md §5.1). Aktor `*`
 * berarti transisi boleh dijalankan aktor mana pun (mis. job sistem).
 */
final class TransitionTable
{
    /**
     * @param  array<string, array<string, array<int, string>>>  $transitions
     */
    public function __construct(private readonly array $transitions) {}

    /**
     * @return array<int, string>
     */
    public function actorsFor(string $from, string $to): array
    {
        return $this->transitions[$from][$to] ?? [];
    }

    public function supports(string $from, string $to): bool
    {
        return isset($this->transitions[$from][$to]);
    }

    public function allows(string $from, string $to, ?string $actor = null): bool
    {
        if (! $this->supports($from, $to)) {
            return false;
        }

        $actors = $this->actorsFor($from, $to);

        if ($actor === null) {
            return in_array('*', $actors, true);
        }

        return in_array('*', $actors, true) || in_array($actor, $actors, true);
    }

    public function assert(string $from, string $to, ?string $actor = null): void
    {
        if ($this->allows($from, $to, $actor)) {
            return;
        }

        throw $this->supports($from, $to)
            ? InvalidTransition::between($from, $to, $actor)
            : InvalidTransition::unknownPair($from, $to);
    }

    /**
     * @return array<int, string>
     */
    public function allStatuses(): array
    {
        return array_values(array_unique([
            ...array_keys($this->transitions),
            ...array_merge(...array_map('array_keys', array_values($this->transitions) ?: [[]])),
        ]));
    }
}
