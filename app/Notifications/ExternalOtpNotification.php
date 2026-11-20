<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification as BaseNotification;

/**
 * Kode OTP portal eksternal. Kanal email saja: kode adalah rahasia dan tidak
 * boleh tersimpan pada notifikasi in-app maupun log (BR-F11-3, BR-F14-4).
 */
class ExternalOtpNotification extends BaseNotification
{
    use Queueable;

    public function __construct(
        public readonly string $code,
        public readonly int $ttlMinutes,
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
            ->subject('Kode OTP portal SIM PKL')
            ->greeting('Halo')
            ->line('Kode OTP Anda: '.$this->code)
            ->line('Kode berlaku '.$this->ttlMinutes.' menit. Jangan bagikan kode ini kepada siapa pun.');
    }
}
