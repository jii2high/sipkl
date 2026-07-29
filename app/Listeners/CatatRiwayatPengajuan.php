<?php

namespace App\Listeners;

use App\Models\PengajuanPkl;
use Spatie\ModelStates\Events\StateChanged;

class CatatRiwayatPengajuan
{
    public function handle(StateChanged $event): void
    {
        $model = $event->model;

        if (! ($model instanceof PengajuanPkl)) {
            return;
        }

        $model->histories()->create([
            'actor_id' => auth()->id() ?? $model->siswa->user_id ?? 1,
            'status_dari' => $event->initialState?->getValue(),
            'status_ke' => $event->finalState->getValue(),
            'catatan' => $model->catatan_kaprog,
            'created_at' => now(),
        ]);
    }
}
