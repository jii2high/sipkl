<?php

namespace App\Notifications;

use App\Models\PengajuanPkl;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class SuratBalasanDitolak extends Notification
{
    use Queueable;

    public function __construct(
        public readonly PengajuanPkl $pengajuan,
        public readonly string $catatan
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
            'judul' => 'Surat Balasan Ditolak',
            'pesan' => 'Surat Balasan untuk pengajuan PKL Anda ke '
                .$this->pengajuan->perusahaan->nama
                .' ditolak oleh Hubin. Alasan: '.$this->catatan
                .' Silakan upload ulang surat balasan yang valid.',
            'pengajuan_id' => $this->pengajuan->id,
            'jenis' => 'surat_balasan_ditolak',
        ];
    }
}
