<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Notifications\Notification as LaravelNotification;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Kanal push opt-in best-effort (BR-F14-1, T10.3). Push hanya dikirim bila
 * pengguna sudah mengaktifkannya dan endpoint dikonfigurasi; kegagalan apa pun
 * dicatat untuk Hubin dan tidak pernah mengganggu email/in-app.
 */
final class PushService
{
    public function optedIn(Model $notifiable): bool
    {
        return $notifiable instanceof User && $notifiable->push_opt_in === true;
    }

    public function configured(): bool
    {
        $endpoint = config('pkl.push.endpoint');

        return is_string($endpoint) && trim($endpoint) !== '';
    }

    public function deliver(Model $notifiable, LaravelNotification $notification): void
    {
        if (! $this->optedIn($notifiable) || ! $this->configured()) {
            return;
        }

        try {
            $this->client()->post((string) config('pkl.push.endpoint'), [
                'user_id' => $notifiable->getKey(),
                'payload' => $this->payload($notification),
            ]);
        } catch (Throwable $exception) {
            app(NotificationService::class)->recordFailure(
                notifiable: $notifiable,
                notification: $notification,
                attempts: 1,
                exception: $exception,
                channel: 'push',
            );
        }
    }

    private function client(): PendingRequest
    {
        return Http::timeout((int) config('pkl.push.timeout_seconds', 5))
            ->acceptJson()
            ->asJson();
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(LaravelNotification $notification): array
    {
        if ($notification instanceof Notification) {
            return [
                'judul' => $notification->judul,
                'pesan' => $notification->pesan,
                'url' => $notification->url,
            ];
        }

        return ['judul' => class_basename($notification)];
    }
}
