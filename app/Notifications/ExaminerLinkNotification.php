<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification as BaseNotification;

/**
 * Tautan portal penguji + PIN. Kanal email saja: PIN adalah rahasia dan tidak
 * boleh tersimpan pada notifikasi in-app maupun log (BR-F11-3, BR-F14-4).
 */
class ExaminerLinkNotification extends BaseNotification
{
    use Queueable;

    public function __construct(
        public readonly string $url,
        public readonly string $pin,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Tautan penilaian sidang SIM PKL')
            ->greeting('Halo')
            ->line('Anda ditunjuk sebagai penguji sidang. Buka tautan berikut dan masukkan PIN.')
            ->action('Buka portal penguji', $this->url)
            ->line('PIN Anda: '.$this->pin)
            ->line('Jangan bagikan tautan atau PIN ini kepada siapa pun.');
    }
}
