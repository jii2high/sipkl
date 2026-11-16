<?php

namespace App\Mail;

use App\Notifications\BaseNotification;
use Illuminate\Notifications\Messages\MailMessage;

final class OtpMail extends BaseNotification
{
    public function __construct(
        private readonly string $code,
    ) {}

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Kode verifikasi SIPKL')
            ->greeting('Halo, '.$notifiable->name)
            ->line('Gunakan kode berikut untuk memverifikasi akun SIPKL Anda:')
            ->line($this->code)
            ->line('Kode berlaku selama 1 menit. Jangan bagikan kode ini kepada siapa pun.');
    }

    /**
     * The OTP is intentionally excluded from the persisted in-app payload.
     *
     * @return array<string, string>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'pesan' => 'Kode verifikasi SIPKL telah dikirim ke email Anda.',
        ];
    }
}
