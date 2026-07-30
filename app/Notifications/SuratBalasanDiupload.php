<?php

namespace App\Notifications;

use App\Models\PengajuanPkl;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class SuratBalasanDiupload extends Notification
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
            'judul' => 'Surat Balasan Diunggah',
            'pesan' => 'Surat Balasan untuk pengajuan PKL Anda ke '
                .$this->pengajuan->perusahaan->nama
                .' berhasil diunggah. Status PKL Anda kini Resmi PKL.',
            'pengajuan_id' => $this->pengajuan->id,
            'jenis' => 'surat_balasan_diupload',
        ];
    }
}
