<?php

namespace App\Notifications;

use App\Models\PengajuanPkl;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PengajuanDiverifikasi extends Notification
{
    use Queueable;

    public function __construct(
        public PengajuanPkl $pengajuan,
        public string $status,
    ) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        $pesan = $this->status === 'disetujui'
            ? 'Pengajuan PKL Anda telah disetujui oleh Kaprog.'
            : 'Pengajuan PKL Anda ditolak oleh Kaprog.';

        return [
            'pengajuan_id' => $this->pengajuan->id,
            'status' => $this->status,
            'pesan' => $pesan,
        ];
    }
}
