<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification as BaseNotification;

/**
 * Tautan penilaian industri untuk pembimbing industri. Kanal email saja: URL
 * memuat token rahasia dan tidak boleh tersimpan pada notifikasi in-app.
 */
class IndustryLinkNotification extends BaseNotification
{
    use Queueable;

    public function __construct(
        public readonly string $url,
        public readonly string $companyName,
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
            ->subject('Tautan penilaian industri PKL')
            ->greeting('Halo')
            ->line('Anda diminta mengisi penilaian industri PKL untuk '.$this->companyName.'.')
            ->action('Buka tautan penilaian', $this->url)
            ->line('Tautan berlaku 7 hari dan hanya dapat dipakai sekali. Jangan bagikan tautan ini kepada siapa pun.');
    }
}
