<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

abstract class BaseNotification extends Notification
{
    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    abstract public function toMail(object $notifiable): MailMessage;

    /**
     * This payload is stored in the notifications table; never include secrets.
     *
     * @return array<string, mixed>
     */
    abstract public function toArray(object $notifiable): array;
}
