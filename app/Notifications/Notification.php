<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification as BaseNotification;

/**
 * Notifikasi bawaan: email + in-app (database). Push opt-in ditambahkan di T10.3
 * dan tidak mengubah kanal wajib ini (BR-F14-1).
 */
class Notification extends BaseNotification
{
    use Queueable;

    public function __construct(
        public readonly string $judul,
        public readonly string $pesan,
        public readonly ?string $url = null,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject($this->judul)
            ->greeting('Halo')
            ->line($this->pesan);

        if ($this->url !== null) {
            $mail->action('Buka di SIM PKL', $this->url);
        }

        return $mail;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'judul' => $this->judul,
            'pesan' => $this->pesan,
            'url' => $this->url,
        ];
    }
}
