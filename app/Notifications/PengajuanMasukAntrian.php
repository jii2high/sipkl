<?php

namespace App\Notifications;

use App\Models\PengajuanPkl;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PengajuanMasukAntrian extends Notification
{
    use Queueable;

    public function __construct(public PengajuanPkl $pengajuan) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'pengajuan_id' => $this->pengajuan->id,
            'siswa_nama' => $this->pengajuan->siswa->user->name,
            'perusahaan_nama' => $this->pengajuan->perusahaan->nama,
            'pesan' => 'Pengajuan PKL baru dari '.$this->pengajuan->siswa->user->name.' masuk antrian verifikasi.',
        ];
    }
}
