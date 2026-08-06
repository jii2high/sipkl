<?php

namespace App\Notifications;

use App\Models\FormulirPenilaian;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PenilaianSubmitted extends Notification
{
    use Queueable;

    public function __construct(
        public readonly FormulirPenilaian $formulir
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
            'judul' => 'Penilaian PKL Selesai',
            'pesan' => 'Formulir Penilaian PKL Anda telah disubmit dengan Nilai Akhir '
                .($this->formulir->nilai_akhir ?? '-').'.',
            'pengajuan_id' => $this->formulir->penugasan->pengajuan_id,
            'jenis' => 'penilaian_submitted',
        ];
    }
}
