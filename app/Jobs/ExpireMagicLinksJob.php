<?php

namespace App\Jobs;

use App\Models\MagicLink;
use App\Services\MagicLinkService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

/**
 * Tandai link kedaluwarsa tiap jam (TRD.md §10, BR-F14-3). Hanya menyentuh baris
 * yang masih aktif sehingga putaran berikutnya tidak mengubah apa pun.
 */
class ExpireMagicLinksJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public function handle(MagicLinkService $links): void
    {
        MagicLink::query()
            ->where('is_active', true)
            ->where('expires_at', '<', now())
            ->chunkById(100, function ($expired) use ($links): void {
                foreach ($expired as $link) {
                    $links->expire($link);
                }
            });
    }
}
