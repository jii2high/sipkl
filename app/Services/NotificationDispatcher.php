<?php

namespace App\Services;

use App\Jobs\SendEmailNotificationJob;
use App\Models\User;
use App\Notifications\BaseNotification;
use Illuminate\Support\Facades\DB;

final class NotificationDispatcher
{
    public function dispatch(User $recipient, BaseNotification $notification): void
    {
        DB::transaction(function () use ($recipient, $notification): void {
            $recipient->notifyNow($notification, ['database']);

            SendEmailNotificationJob::dispatch($recipient, $notification);
        });
    }
}
