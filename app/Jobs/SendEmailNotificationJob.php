<?php

namespace App\Jobs;

use App\Models\NotificationDeliveryFailure;
use App\Models\User;
use App\Notifications\BaseNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Notifications\Channels\MailChannel;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use RuntimeException;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Throwable;

class SendEmailNotificationJob implements ShouldBeEncrypted, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /**
     * @var list<int>
     */
    public array $backoff = [10, 30];

    public function __construct(
        public User $recipient,
        public BaseNotification $notification,
    ) {}

    public function handle(MailChannel $mailChannel): void
    {
        try {
            $mailChannel->send($this->recipient, $this->notification);
        } catch (TransportExceptionInterface) {
            throw new RuntimeException('Email notification delivery failed.');
        } catch (Throwable) {
            $failure = new RuntimeException('Email notification delivery failed.');

            if ($this->job === null) {
                throw $failure;
            }

            $this->fail($failure);
        }
    }

    public function failed(Throwable $exception): void
    {
        $failedAt = now();
        $attemptCount = $this->attempts();

        NotificationDeliveryFailure::create([
            'channel' => 'email',
            'recipient_email' => $this->recipient->email,
            'recipient_user_id' => $this->recipient->getKey(),
            'notification_type' => $this->notification::class,
            'attempt_count' => $attemptCount,
            'failure_summary' => "Pengiriman email gagal setelah {$attemptCount} percobaan.",
            'first_failed_at' => $failedAt,
            'last_failed_at' => $failedAt,
        ]);
    }
}
