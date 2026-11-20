<?php

namespace App\Jobs;

use App\Actions\Visitasi\ExpireVisitasi;
use App\Jobs\Concerns\RunsOncePerJakartaDay;
use App\Models\SuratTugasVisitasi;
use App\Support\JakartaTime;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

class ExpireVisitasiJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, RunsOncePerJakartaDay;

    public function handle(ExpireVisitasi $expire): void
    {
        if (! $this->shouldRunToday()) {
            return;
        }

        SuratTugasVisitasi::query()
            ->whereIn('status', ['disetujui', 'dicetak', 'aktif'])
            ->whereNotNull('nomor_surat')
            ->where('tanggal_selesai', '<', JakartaTime::today()->toDateString())
            ->chunkById(100, function ($visitasiList) use ($expire): void {
                foreach ($visitasiList as $visitasi) {
                    $expire->handle($visitasi);
                }
            });
    }
}
