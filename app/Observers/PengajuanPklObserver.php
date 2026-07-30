<?php

namespace App\Observers;

use App\Models\PengajuanPkl;

class PengajuanPklObserver
{
    public function created(PengajuanPkl $pengajuan): void
    {
        $pengajuan->histories()->create([
            'actor_id' => auth()->id() ?? $pengajuan->siswa->user_id ?? 1,
            'status_dari' => null,
            'status_ke' => $pengajuan->status,
            'catatan' => $pengajuan->catatan_kaprog,
            'created_at' => now(),
        ]);
    }

    public function updated(PengajuanPkl $pengajuan): void
    {
        if ($pengajuan->isDirty('status')) {
            $pengajuan->histories()->create([
                'actor_id' => auth()->id() ?? $pengajuan->siswa->user_id ?? 1,
                'status_dari' => $pengajuan->getOriginal('status'),
                'status_ke' => $pengajuan->status,
                'catatan' => $pengajuan->catatan_kaprog,
                'created_at' => now(),
            ]);
        }
    }
}
