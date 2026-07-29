<?php

namespace App\Notifications;

use App\Models\PengajuanPkl;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Notification;

class PengajuanMasukAntrian extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public PengajuanPkl $pengajuan,
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): DatabaseMessage
    {
        return new DatabaseMessage([
            'title' => 'Pengajuan PKL Baru',
            'message' => "{$this->pengajuan->siswa->user->name} mengajukan PKL ke {$this->pengajuan->perusahaan->nama}.",
            'pengajuan_id' => $this->pengajuan->id,
            'type' => 'pengajuan_masuk',
        ]);
    }
}
