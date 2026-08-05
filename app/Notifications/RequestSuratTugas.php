<?php

namespace App\Notifications;

use App\Models\KompetensiKeahlian;
use App\Models\PeriodePkl;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class RequestSuratTugas extends Notification
{
    use Queueable;

    public function __construct(
        public readonly PeriodePkl $periode,
        public readonly ?KompetensiKeahlian $kompetensiKeahlian,
        public readonly string $requestor,
        public readonly ?string $catatan = null,
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
        $namaJurusan = $this->kompetensiKeahlian?->nama;

        return [
            'judul' => 'Permintaan Surat Tugas Monitoring',
            'pesan' => 'Kaprog '.$this->requestor
                .' meminta Surat Tugas Monitoring periode '.$this->periode->nama
                .' untuk jurusan '.($namaJurusan ?? '-')
                .($this->catatan ? ".\nCatatan: {$this->catatan}" : '.'),
            'periode_id' => $this->periode->id,
            'kompetensi_keahlian_id' => $this->kompetensiKeahlian?->id,
            'jenis' => 'request_surat_tugas',
        ];
    }
}
