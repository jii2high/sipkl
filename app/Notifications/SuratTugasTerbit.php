<?php

namespace App\Notifications;

use App\Models\SuratTugasMonitoring;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class SuratTugasTerbit extends Notification
{
    use Queueable;

    public function __construct(
        public readonly SuratTugasMonitoring $suratTugas
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
        $cakupan = $this->suratTugas->lingkup === 'scoped'
            ? 'jurusan '.$this->suratTugas->kompetensiKeahlian->nama
            : 'seluruh jurusan';

        return [
            'judul' => 'Surat Tugas Monitoring Diterbitkan',
            'pesan' => 'Surat Tugas Monitoring periode '.$this->suratTugas->periode->nama
                ." ({$cakupan}) telah diterbitkan. Silakan isi Laporan Kunjungan.",
            'surat_tugas_id' => $this->suratTugas->id,
            'nomor_surat' => $this->suratTugas->nomor_surat,
            'jenis' => 'surat_tugas_terbit',
        ];
    }
}
