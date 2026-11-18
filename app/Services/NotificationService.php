<?php

namespace App\Services;

use App\Jobs\SendNotificationJob;
use App\Models\NotificationDeliveryFailure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notification as LaravelNotification;
use Throwable;

/**
 * Gerbang tunggal pengiriman notifikasi (TRD.md §10, BR-F14-1/2). Semua pemanggil
 * memakai `send()` agar email + in-app selalu sepasang, dikirim setelah commit, dan
 * punya retry transient yang seragam. Kegagalan permanen dicatat untuk Hubin.
 */
final class NotificationService
{
    /**
     * @param  array<string, mixed>  $context  Konteks untuk pencatatan kegagalan (mis. `related`).
     */
    public function send(Model $notifiable, LaravelNotification $notification, array $context = []): void
    {
        SendNotificationJob::dispatch($notifiable, $notification, $context);
    }

    /**
     * Catat kegagalan permanen setelah retry habis (BR-F14-2). Baris belum selesai
     * dengan penerima+jenis yang sama diperbarui, bukan digandakan, sehingga Hubin
     * melihat satu baris per masalah dengan `attempt_count` akumulatif.
     *
     * @param  array<string, mixed>  $context
     */
    public function recordFailure(
        Model $notifiable,
        LaravelNotification $notification,
        int $attempts,
        ?Throwable $exception = null,
        array $context = [],
    ): NotificationDeliveryFailure {
        $related = $context['related'] ?? null;
        $now = now();
        $recipientId = $notifiable->getKey();
        $recipientEmail = $notifiable->getAttribute('email');

        $existing = NotificationDeliveryFailure::query()
            ->where('recipient_user_id', $recipientId)
            ->where('notification_type', $notification::class)
            ->whereNull('resolved_at')
            ->first();

        if ($existing !== null) {
            $existing->update([
                'attempt_count' => $existing->attempt_count + $attempts,
                'failure_summary' => $this->summary($exception),
                'last_failed_at' => $now,
            ]);

            return $existing;
        }

        return NotificationDeliveryFailure::query()->create([
            'channel' => $this->channel($notifiable, $notification),
            'recipient_email' => is_string($recipientEmail) ? $recipientEmail : null,
            'recipient_user_id' => $recipientId,
            'notification_type' => $notification::class,
            'related_type' => $related instanceof Model ? $related::class : null,
            'related_id' => $related instanceof Model ? $related->getKey() : null,
            'attempt_count' => $attempts,
            'failure_summary' => $this->summary($exception),
            'first_failed_at' => $now,
            'last_failed_at' => $now,
        ]);
    }

    /** Kanal utama yang gagal, untuk pemfilteran Hubin. */
    private function channel(Model $notifiable, LaravelNotification $notification): string
    {
        $channels = $notification->via($notifiable);

        return match (true) {
            in_array('mail', $channels, true) => 'email',
            in_array('database', $channels, true) => 'in_app',
            default => 'push',
        };
    }

    /** Ringkasan teknis tanpa isi notifikasi maupun rahasia (BR-F14-4). */
    private function summary(?Throwable $exception): string
    {
        if ($exception === null) {
            return 'Pengiriman tidak berhasil setelah percobaan maksimum.';
        }

        return mb_substr($exception::class.': '.$exception->getMessage(), 0, 1000);
    }
}
