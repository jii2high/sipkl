<?php

namespace App\Actions\Visitasi;

use App\Actions\Action;
use App\Models\SuratTugasVisitasi;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use RuntimeException;

final class RejectVisitasi extends Action
{
    public function handle(mixed ...$arguments): int
    {
        [$visitasi, $hubin, $reason] = $arguments;

        if (! $visitasi instanceof SuratTugasVisitasi || ! $hubin instanceof User) {
            throw new RuntimeException('Visitasi dan Hubin wajib diisi.');
        }

        Gate::forUser($hubin)->authorize('reject', $visitasi);
        $reason = trim((string) $reason);
        $minimum = (int) config('pkl.rejection_note.min_length', 10);

        if (mb_strlen($reason) < $minimum) {
            throw ValidationException::withMessages(['reason' => __('pkl.rejection_note.min', ['min' => $minimum])]);
        }

        return $this->transaction(function () use ($visitasi, $reason): int {
            $locked = SuratTugasVisitasi::query()->whereKey($visitasi->getKey())->lockForUpdate()->firstOrFail();

            if ($locked->status !== 'diajukan') {
                return 0;
            }

            $this->transitionStatus($locked, 'ditolak', 'diajukan', ['rejection_note' => $reason]);
            $this->audit($locked, 'visitasi_ditolak', 'diajukan', 'ditolak', ['alasan' => $reason], 'Pengajuan visitasi ditolak');

            return 1;
        });
    }
}
