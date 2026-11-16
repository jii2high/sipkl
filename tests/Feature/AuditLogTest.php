<?php

use App\Models\NotificationDeliveryFailure;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

it('menulis baris activity_log lewat helper activity()', function () {
    activity()->log('uji audit');

    $row = DB::table('activity_log')->where('description', 'uji audit')->first();

    expect($row)->not->toBeNull()
        ->and($row->log_name)->toBe('default')
        ->and(json_decode($row->properties, true))->toBe([]);
});

it('menyimpan notifikasi in-app untuk user', function () {
    $user = User::factory()->create();

    $user->notifications()->create([
        'id' => (string) Str::uuid(),
        'type' => 'App\\Notifications\\UjiNotifikasi',
        'data' => ['pesan' => 'halo'],
    ]);

    $notification = $user->notifications()->first();

    expect($user->notifications()->count())->toBe(1)
        ->and($notification->data)->toBe(['pesan' => 'halo'])
        ->and($notification->read_at)->toBeNull();
});

it('mencatat kegagalan pengiriman notifikasi', function () {
    $user = User::factory()->create();

    $failure = NotificationDeliveryFailure::create([
        'channel' => 'email',
        'recipient_email' => $user->email,
        'recipient_user_id' => $user->id,
        'notification_type' => 'App\\Notifications\\UjiNotifikasi',
        'attempt_count' => 3,
        'failure_summary' => 'SMTP timeout setelah 3 percobaan',
        'first_failed_at' => now()->subHour(),
        'last_failed_at' => now(),
    ]);

    expect($failure->exists)->toBeTrue()
        ->and($failure->fresh()->recipient->id)->toBe($user->id);
});

it('menolak channel kegagalan yang tidak dikenal', function () {
    expect(fn () => NotificationDeliveryFailure::create([
        'channel' => 'sms',
        'notification_type' => 'x',
        'attempt_count' => 1,
        'failure_summary' => 'x',
        'first_failed_at' => now(),
        'last_failed_at' => now(),
    ]))->toThrow(QueryException::class);
});
