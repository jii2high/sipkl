<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Notification;

class DraftMonitoringDitolak extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $catatan) {}

    /**
     * @return string[]
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): DatabaseMessage
    {
        return new DatabaseMessage([
            'judul' => 'Draft Surat Tugas Monitoring Ditolak',
            'pesan' => 'Draft Surat Tugas monitoring Anda ditolak oleh Hubin.',
            'catatan' => $this->catatan,
        ]);
    }
}
