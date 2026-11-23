<?php

namespace App\Jobs;

use App\Services\NotificationService;
use App\Services\PushService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Notifications\Notification as LaravelNotification;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

/**
 * Kirim notifikasi email + in-app. Retry transient maksimal `pkl.mail.retry_max`
 * (BR-F14-2); kegagalan permanen dicatat untuk Hubin. Dikirim setelah commit
 * agar notifikasi tidak keluar saat transaksi di-rollback.
 */
class SendNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries;

    public function __construct(
        public Model $notifiable,
        public LaravelNotification $notification,
        public array $context = [],
    ) {
        $this->tries = (int) config('pkl.mail.retry_max', 3);
        // Notifikasi dikirim setelah transaksi commit (TRD.md §8).
        $this->afterCommit = true;
    }

    /**
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [10, 60, 300];
    }

    public function handle(): void
    {
        $this->notifiable->notify($this->notification);

        // Push best-effort: kegagalannya dicatat sendiri dan tidak melempar (BR-F14-1).
        app(PushService::class)->deliver($this->notifiable, $this->notification);
    }

    public function failed(?Throwable $exception): void
    {
        app(NotificationService::class)->recordFailure(
            notifiable: $this->notifiable,
            notification: $this->notification,
            attempts: $this->attempts() > 0 ? $this->attempts() : $this->tries,
            exception: $exception,
            context: $this->context,
        );
    }
}
