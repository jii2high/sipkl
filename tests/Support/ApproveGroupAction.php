<?php

namespace Tests\Support;

use App\Actions\Action;
use App\Models\PengajuanPkl;

/**
 * Action uji: memakai kerangka Action untuk membuktikan transisi berpagar status,
 * idempotensi, dan audit dalam satu transaksi (TRD.md §8).
 */
class ApproveGroupAction extends Action
{
    public function handle(mixed ...$arguments): int
    {
        /** @var PengajuanPkl $pkl */
        $pkl = $arguments[0];

        return $this->transaction(function () use ($pkl): int {
            $affected = $this->transitionStatus($pkl, 'surat_terbit', 'terbentuk');

            if ($affected === 1) {
                $this->audit($pkl, 'status_diubah', 'terbentuk', 'surat_terbit');
            }

            return $affected;
        });
    }
}
