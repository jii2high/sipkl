<?php

namespace App\Actions\Visitasi;

use App\Actions\Action;
use App\Models\SuratTugasVisitasi;
use App\Support\JakartaTime;

final class ExpireVisitasi extends Action
{
    public function handle(mixed ...$arguments): int
    {
        /** @var SuratTugasVisitasi $visitasi */
        [$visitasi] = $arguments;

        return $this->transaction(function () use ($visitasi): int {
            $locked = SuratTugasVisitasi::query()->whereKey($visitasi->getKey())->lockForUpdate()->first();

            if ($locked === null
                || ! in_array($locked->status, ['disetujui', 'dicetak', 'aktif'], true)
                || $locked->tanggal_selesai->toDateString() >= JakartaTime::today()->toDateString()) {
                return 0;
            }

            $changed = $this->transitionStatus($locked, 'kedaluwarsa', (string) $locked->status);

            if ($changed === 1) {
                $this->audit(
                    $locked,
                    'visitasi_kedaluwarsa',
                    (string) $locked->status,
                    'kedaluwarsa',
                    ['tanggal_selesai' => $locked->tanggal_selesai->toDateString()],
                    'Surat tugas visitasi kedaluwarsa',
                );
            }

            return $changed;
        });
    }
}
