<?php

namespace App\Notifications;

use App\Models\PengajuanPkl;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Notification;

class PengajuanDiverifikasi extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public PengajuanPkl $pengajuan,
        public string $status,
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): DatabaseMessage
    {
        $statusText = $this->status === 'disetujui' ? 'disetujui' : 'ditolak';

        return new DatabaseMessage([
            'title' => 'Pengajuan PKL '.ucfirst($statusText),
            'message' => "Pengajuan PKL ke {$this->pengajuan->perusahaan->nama} {$statusText} oleh Kaprog.",
            'pengajuan_id' => $this->pengajuan->id,
            'type' => 'pengajuan_diverifikasi',
            'status' => $this->status,
        ]);
    }
}
