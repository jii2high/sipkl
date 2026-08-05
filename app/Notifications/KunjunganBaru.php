<?php

namespace App\Notifications;

use App\Models\KunjunganMonitoring;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;

class KunjunganBaru extends Notification
{
    use Queueable;

    public function __construct(
        public readonly KunjunganMonitoring $kunjungan
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $siswa = $this->kunjungan->penugasan->pengajuan->siswa->user->name;

        return [
            'judul' => 'Laporan Kunjungan Monitoring Baru',
            'pesan' => 'Kunjungan monitoring untuk siswa '.$siswa
                .' pada tanggal '
                .Carbon::parse($this->kunjungan->tanggal_kunjungan)->translatedFormat('d F Y')
                .' telah dicatat.',
            'kunjungan_id' => $this->kunjungan->id,
            'pengajuan_id' => $this->kunjungan->penugasan->pengajuan_id,
            'jenis' => 'kunjungan_baru',
        ];
    }
}
