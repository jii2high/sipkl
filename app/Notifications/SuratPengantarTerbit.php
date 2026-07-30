<?php

namespace App\Notifications;

use App\Models\PengajuanPkl;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class SuratPengantarTerbit extends Notification
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
            'judul' => 'Surat Pengantar Diterbitkan',
            'pesan' => 'Surat Pengantar untuk pengajuan PKL Anda ke '
                .$this->pengajuan->perusahaan->nama
                .' telah diterbitkan. Silakan upload Surat Balasan dari perusahaan.',
            'pengajuan_id' => $this->pengajuan->id,
            'jenis' => 'surat_pengantar_terbit',
        ];
    }
}
