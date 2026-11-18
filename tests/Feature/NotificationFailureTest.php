<?php

use App\Jobs\SendNotificationJob;
use App\Models\NotificationDeliveryFailure;
use App\Models\User;
use App\Notifications\Notification;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Queue;

test('kegagalan permanen dicatat sekali dengan akumulasi percobaan', function () {
    $user = User::factory()->create();
    $service = app(NotificationService::class);
    $notification = new Notification('Judul', 'Pesan');

    $pertama = $service->recordFailure($user, $notification, 3, new RuntimeException('SMTP tidak merespons'), ['token' => 'rahasia123']);
    $kedua = $service->recordFailure($user, $notification, 3, new RuntimeException('SMTP tidak merespons'));

    expect(NotificationDeliveryFailure::query()->count())->toBe(1)
        ->and($kedua->id)->toBe($pertama->id)
        ->and($kedua->fresh()->attempt_count)->toBe(6)
        ->and($kedua->fresh()->first_failed_at->equalTo($pertama->fresh()->first_failed_at))->toBeTrue()
        ->and($pertama->channel)->toBe('email')
        ->and($pertama->recipient_user_id)->toBe($user->id)
        ->and($pertama->failure_summary)->toContain('RuntimeException')
        ->and($pertama->failure_summary)->not->toContain('rahasia123');
});

test('job notifikasi yang gagal permanen mencatat kegagalan untuk Hubin', function () {
    $user = User::factory()->create();

    (new SendNotificationJob($user, new Notification('Judul', 'Pesan')))->failed(new RuntimeException('mailbox penuh'));

    expect(NotificationDeliveryFailure::query()->count())->toBe(1)
        ->and(NotificationDeliveryFailure::query()->firstOrFail()->channel)->toBe('email');
});

test('pengiriman memakai job dengan retry dan afterCommit sesuai BR-F14-2', function () {
    Queue::fake();
    $user = User::factory()->create();

    app(NotificationService::class)->send($user, new Notification('Judul', 'Pesan'));

    Queue::assertPushed(SendNotificationJob::class);

    $job = new SendNotificationJob($user, new Notification('Judul', 'Pesan'));

    expect($job->tries)->toBe((int) config('pkl.mail.retry_max'))
        ->and($job->afterCommit)->toBeTrue();
});
