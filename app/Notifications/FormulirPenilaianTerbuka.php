<?php

namespace App\Notifications;

use App\Models\PengajuanPkl;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class FormulirPenilaianTerbuka extends Notification
{
    use Queueable;

    public function __construct(
        public readonly PengajuanPkl $pengajuan
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
        return [
            'judul' => 'Formulir Penilaian Terbuka',
            'pesan' => 'Siswa '.$this->pengajuan->siswa->user->name
                .' telah mengunggah Laporan Akhir PKL. Formulir Penilaian kini dapat diisi.',
            'pengajuan_id' => $this->pengajuan->id,
            'jenis' => 'formulir_penilaian_terbuka',
        ];
    }
}
