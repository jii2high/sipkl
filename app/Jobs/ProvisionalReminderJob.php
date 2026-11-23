<?php

namespace App\Jobs;

use App\Enums\PengajuanStatus;
use App\Models\PengajuanSiswa;
use App\Notifications\Notification;
use App\Services\NotificationService;
use App\Support\JakartaTime;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;

/**
 * Pengingat harian siswa PKL provisional (BR-F3-4): email + in-app, tanpa
 * eskalasi. Idempoten per tanggal Jakarta lewat `last_provisional_reminder_at`,
 * jadi menjalankan job dua kali pada hari yang sama hanya mengirim sekali.
 */
class ProvisionalReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 3;

    /** @return array<int, int> */
    public function backoff(): array
    {
        return [30, 120, 600];
    }

    public function handle(NotificationService $notifications): void
    {
        $today = JakartaTime::dateKey();

        PengajuanSiswa::query()
            ->where('status', PengajuanStatus::SedangPkl->value)
            ->where('is_provisional', true)
            ->where(fn ($query) => $query
                ->whereNull('last_provisional_reminder_at')
                ->orWhere('last_provisional_reminder_at', '<', $today))
            ->with('siswa')
            ->chunkById(100, function ($rows) use ($notifications, $today): void {
                foreach ($rows as $pengajuan) {
                    $siswa = $pengajuan->siswa;

                    if ($siswa === null) {
                        continue;
                    }

                    $notifications->send(
                        $siswa,
                        new Notification(
                            'Lengkapi dokumen fisik PKL',
                            'Dokumen fisik PKL kamu belum dikonfirmasi Hubin. Serahkan dokumen ke Hubin agar status provisional selesai.',
                            url('/siswa'),
                        ),
                        ['related' => $pengajuan],
                    );

                    DB::table('pengajuan_siswa')
                        ->where('id', $pengajuan->getKey())
                        ->update(['last_provisional_reminder_at' => $today]);
                }
            });
    }
}
