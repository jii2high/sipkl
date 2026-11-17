<?php

use App\Jobs\SendEmailNotificationJob;
use App\Models\NotificationDeliveryFailure;
use App\Models\User;
use App\Notifications\BaseNotification;
use App\Services\NotificationDispatcher;
use Illuminate\Contracts\Queue\Job as QueueJob;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\Channels\MailChannel;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Queue;
use Symfony\Component\Mailer\Exception\TransportException;

uses(RefreshDatabase::class);

final class UjiNotifikasi extends BaseNotification
{
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)->subject('Pemberitahuan')->line('Ada pembaruan.');
    }

    public function toArray(object $notifiable): array
    {
        return ['pesan' => 'Ada pembaruan.'];
    }
}

it('menyimpan notifikasi in-app dan mengantrekan email dalam satu dispatch', function () {
    Queue::fake();

    $user = User::factory()->create();
    $notification = new UjiNotifikasi;

    app(NotificationDispatcher::class)->dispatch($user, $notification);

    expect($user->notifications()->count())->toBe(1)
        ->and($user->notifications()->first()->data)->toBe(['pesan' => 'Ada pembaruan.']);

    Queue::assertPushed(SendEmailNotificationJob::class, function (SendEmailNotificationJob $job) use ($user, $notification): bool {
        return $job->recipient->is($user)
            && $job->notification->id === $notification->id
            && $job->tries === 3
            && $job->backoff === [10, 30]
            && $job instanceof ShouldBeEncrypted;
    });
});

it('tidak mengulang kanal in-app yang berhasil saat email dicoba ulang', function () {
    Queue::fake();

    $user = User::factory()->create();
    $notification = new UjiNotifikasi;
    app(NotificationDispatcher::class)->dispatch($user, $notification);

    $mailChannel = Mockery::mock(MailChannel::class);
    $mailChannel->shouldReceive('send')->once()->andThrow(new TransportException('secret OTP value'));
    $mailChannel->shouldReceive('send')->once()->andReturnNull();

    $job = new SendEmailNotificationJob($user, $notification);

    try {
        $job->handle($mailChannel);
    } catch (RuntimeException $exception) {
        expect($exception->getMessage())->toBe('Email notification delivery failed.')
            ->not->toContain('secret OTP value');
    }

    $job->handle($mailChannel);

    expect($user->notifications()->count())->toBe(1)
        ->and(NotificationDeliveryFailure::query()->count())->toBe(0);
});

it('mencatat satu kegagalan aman setelah tiga percobaan email gagal', function () {
    Queue::fake();

    $user = User::factory()->create();
    $notification = new UjiNotifikasi;
    app(NotificationDispatcher::class)->dispatch($user, $notification);

    $mailChannel = Mockery::mock(MailChannel::class);
    $mailChannel->shouldReceive('send')->times(3)->andThrow(new TransportException('secret OTP value'));

    $job = new SendEmailNotificationJob($user, $notification);

    for ($attempt = 0; $attempt < 3; $attempt++) {
        try {
            $job->handle($mailChannel);
        } catch (RuntimeException $exception) {
            expect($exception->getMessage())->toBe('Email notification delivery failed.')
                ->not->toContain('secret OTP value');
        }
    }

    $queueJob = Mockery::mock(QueueJob::class);
    $queueJob->shouldReceive('attempts')->andReturn(3);
    $job->setJob($queueJob);
    $job->failed(new RuntimeException('Email notification delivery failed.'));

    $failure = NotificationDeliveryFailure::query()->sole();

    expect($failure->channel)->toBe('email')
        ->and($failure->recipient_user_id)->toBe($user->id)
        ->and($failure->attempt_count)->toBe(3)
        ->and($failure->failure_summary)->toBe('Pengiriman email gagal setelah 3 percobaan.')
        ->and($failure->failure_summary)->not->toContain('secret OTP value')
        ->and($user->notifications()->count())->toBe(1);
});

it('mencatat kegagalan permanen tanpa mengulang email', function () {
    $user = User::factory()->create();
    $job = (new SendEmailNotificationJob($user, new UjiNotifikasi))->withFakeQueueInteractions();

    $mailChannel = Mockery::mock(MailChannel::class);
    $mailChannel->shouldReceive('send')->once()->andThrow(new InvalidArgumentException('data sensitif'));

    $job->handle($mailChannel);
    $job->assertFailed();
    $job->failed(new RuntimeException('Email notification delivery failed.'));

    $failure = NotificationDeliveryFailure::query()->sole();

    expect($failure->attempt_count)->toBe(1)
        ->and($failure->failure_summary)->toBe('Pengiriman email gagal setelah 1 percobaan.')
        ->and($failure->failure_summary)->not->toContain('data sensitif');
});
