<?php

namespace App\Listeners;

use App\Notifications\PengajuanDiverifikasi;
use App\Notifications\PengajuanMasukAntrian;
use App\States\Pengajuan\DiajukanKaprog;
use App\States\Pengajuan\DitolakKaprog;
use App\States\Pengajuan\SiapTerbit;
use Spatie\ModelStates\Events\StateChanged;

class CatatRiwayatPengajuan
{
    public function handle(StateChanged $event): void
    {
        $event->model->histories()->create([
            'actor_id' => auth()->id(),
            'status_dari' => $event->initialState?->getMorphClass(),
            'status_ke' => $event->finalState->getMorphClass(),
            'catatan' => $event->model->catatan_kaprog,
            'created_at' => now(),
        ]);

        $pengajuan = $event->model;
        $siswaUser = $pengajuan->siswa->user;

        if ($event->finalState instanceof DiajukanKaprog) {
            $kaprogUser = $pengajuan->siswa->kompetensiKeahlian?->guruProfiles
                ->where('jabatan', 'Kaprog')->first()?->user;
            $kaprogUser?->notify(new PengajuanMasukAntrian($pengajuan));
        }

        if ($event->finalState instanceof SiapTerbit) {
            $siswaUser?->notify(new PengajuanDiverifikasi($pengajuan, 'disetujui'));
        }

        if ($event->finalState instanceof DitolakKaprog) {
            $siswaUser?->notify(new PengajuanDiverifikasi($pengajuan, 'ditolak'));
        }
    }
}
