<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification as BaseNotification;

/**
 * Tautan pengesahan step 3/4. Kanal email saja: URL memuat token rahasia dan
 * tidak boleh tersimpan pada notifikasi in-app (BR-F11-3).
 */
class SignerLinkNotification extends BaseNotification
{
    use Queueable;

    public function __construct(
        public readonly string $url,
        public readonly string $stepLabel,
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
            ->subject('Tautan pengesahan laporan PKL')
            ->greeting('Halo')
            ->line('Anda diminta mengesahkan laporan PKL pada langkah '.$this->stepLabel.'.')
            ->action('Buka tautan pengesahan', $this->url)
            ->line('Tautan hanya dapat dipakai sekali. Jangan bagikan tautan ini kepada siapa pun.');
    }
}
