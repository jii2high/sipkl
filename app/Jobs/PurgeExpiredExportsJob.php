<?php

namespace App\Jobs;

use App\Models\AccreditationExport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Storage;

/**
 * Hapus berkas dan baris ekspor akreditasi yang melewati masa simpan 30 hari
 * (KP-F13-3, TRD.md A10). Referensi `retry_of_id` dilepas lebih dulu agar baris
 * lama tetap dapat dihapus.
 */
class PurgeExpiredExportsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public function handle(): void
    {
        $expired = AccreditationExport::query()
            ->where('status', 'completed')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->get();

        if ($expired->isEmpty()) {
            return;
        }

        $ids = $expired->pluck('id');
        AccreditationExport::query()->whereIn('retry_of_id', $ids)->update(['retry_of_id' => null]);

        foreach ($expired as $export) {
            if (is_string($export->snapshot_file_path)) {
                Storage::disk('local')->delete($export->snapshot_file_path);
            }

            $export->delete();
        }
    }
}
