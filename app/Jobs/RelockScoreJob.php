<?php

namespace App\Jobs;

use App\Models\MagicLink;
use App\Models\NilaiSidangRevision;
use App\Services\MagicLinkService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

/**
 * Tutup window revisi nilai yang lewat 24 jam (KP-F9-4, TRD.md §10). Hanya baris
 * `menunggu_revisi` yang disentuh; nilai tetap seperti hasil penguncian terakhir
 * dan tautan unlock dicabut agar tidak dapat dipakai lagi.
 */
class RelockScoreJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public function handle(MagicLinkService $links): void
    {
        NilaiSidangRevision::query()
            ->where('status', 'menunggu_revisi')
            ->where('unlock_expires_at', '<', now())
            ->chunkById(100, function ($expired) use ($links): void {
                foreach ($expired as $revision) {
                    $link = $revision->unlock_magic_link_id === null
                        ? null
                        : MagicLink::query()->find($revision->unlock_magic_link_id);

                    if ($link !== null) {
                        $links->revoke($link, null, 'Batas waktu revisi berakhir');
                    }

                    $revision->update(['status' => 'auto_relocked']);
                }
            });
    }
}
