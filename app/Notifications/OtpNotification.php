<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification as BaseNotification;

/**
 * Kode OTP first-login. Hanya kanal email: kode adalah rahasia dan tidak boleh
 * tersimpan di tabel `notifications` (kanal in-app) maupun log (BR-F14-4).
 */
class OtpNotification extends BaseNotification
{
    use Queueable;

    public function __construct(
        public readonly string $code,
        public readonly int $ttlSeconds,
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
            ->subject('Kode OTP SIM PKL')
            ->greeting('Halo '.$notifiable->name)
            ->line('Kode OTP Anda: '.$this->code)
            ->line('Kode berlaku '.$this->ttlSeconds.' detik. Jangan bagikan kode ini kepada siapa pun.');
    }
}
