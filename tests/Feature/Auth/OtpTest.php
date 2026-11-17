<?php

use App\Jobs\SendEmailNotificationJob;
use App\Mail\OtpMail;
use App\Models\OtpCode;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

it('menerbitkan OTP enam digit yang hanya disimpan sebagai hash dan mengantrekan email terenkripsi', function () {
    Queue::fake();
    $this->travelTo(now()->startOfSecond());
    $user = User::factory()->create();

    $otp = app(OtpService::class)->issue($user);

    expect($otp->code_hash)->toMatch('/^[a-f0-9]{64}$/')
        ->and($otp->attempt_count)->toBe(0)
        ->and($otp->expires_at->equalTo(now()->addMinute()))->toBeTrue();

    Queue::assertPushed(SendEmailNotificationJob::class, function (SendEmailNotificationJob $job) use ($user, $otp): bool {
        $email = $job->notification;

        return $job->recipient->is($user)
            && $job instanceof ShouldBeEncrypted
            && $email instanceof OtpMail
            && hash('sha256', extractOtpForTesting($email)) === $otp->code_hash;
    });

    expect($user->notifications()->sole()->data)->toBe([
        'pesan' => 'Kode verifikasi SIPKL telah dikirim ke email Anda.',
    ]);
});

it('menginvalidasi OTP lama saat menerbitkan OTP baru', function () {
    Queue::fake();
    $user = User::factory()->create();
    $service = app(OtpService::class);
    $first = $service->issue($user);
    $second = $service->issue($user);

    expect($first->fresh()->invalidated_at)->not->toBeNull()
        ->and($second->fresh()->invalidated_at)->toBeNull()
        ->and($user->otpCodes()->whereNull('invalidated_at')->whereNull('verified_at')->count())->toBe(1);
});

it('memverifikasi OTP yang benar satu kali saja', function () {
    Queue::fake();
    $user = User::factory()->create();
    $service = app(OtpService::class);
    $otp = $service->issue($user);
    $code = extractOtpForTesting(Queue::pushed(SendEmailNotificationJob::class)->first()->notification);

    expect($service->verify($user, $code))->toBeTrue()
        ->and($otp->fresh()->verified_at)->not->toBeNull()
        ->and($service->verify($user, $code))->toBeFalse();
});

it('menolak dan menginvalidasi OTP yang kedaluwarsa', function () {
    Queue::fake();
    $user = User::factory()->create();
    $otp = app(OtpService::class)->issue($user);
    $this->travel(61)->seconds();

    expect(app(OtpService::class)->verify($user, '123456'))->toBeFalse()
        ->and($otp->fresh()->invalidated_at)->not->toBeNull()
        ->and($otp->fresh()->attempt_count)->toBe(0);
});

it('menginvalidasi OTP pada percobaan keempat setelah tiga verifikasi salah', function () {
    Queue::fake();
    $user = User::factory()->create();
    $service = app(OtpService::class);
    $otp = $service->issue($user);

    expect($service->verify($user, '000000'))->toBeFalse()
        ->and($service->verify($user, '000000'))->toBeFalse()
        ->and($service->verify($user, '000000'))->toBeFalse()
        ->and($otp->fresh()->attempt_count)->toBe(3)
        ->and($otp->fresh()->invalidated_at)->toBeNull()
        ->and($service->verify($user, '000000'))->toBeFalse()
        ->and($otp->fresh()->invalidated_at)->not->toBeNull()
        ->and($otp->fresh()->attempt_count)->toBe(3);
});

it('menolak OTP yang benar pada percobaan keempat setelah tiga percobaan gagal', function () {
    Queue::fake();
    $user = User::factory()->create();
    $service = app(OtpService::class);
    $otp = $service->issue($user);
    $code = extractOtpForTesting(Queue::pushed(SendEmailNotificationJob::class)->first()->notification);

    $service->verify($user, '000000');
    $service->verify($user, '000000');
    $service->verify($user, '000000');

    expect($service->verify($user, $code))->toBeFalse()
        ->and($otp->fresh()->invalidated_at)->not->toBeNull()
        ->and($otp->fresh()->verified_at)->toBeNull();
});

it('menolak penerbitan OTP untuk akun nonaktif atau yang sudah menyelesaikan first-login', function () {
    Queue::fake();
    $service = app(OtpService::class);
    $nonaktif = User::factory()->nonaktif()->create();
    $selesai = User::factory()->sudahGantiPassword()->create();

    expect(fn () => $service->issue($nonaktif))->toThrow(DomainException::class)
        ->and(fn () => $service->issue($selesai))->toThrow(DomainException::class)
        ->and(OtpCode::query()->count())->toBe(0);
});

function extractOtpForTesting(OtpMail $notification): string
{
    $reflection = new ReflectionProperty($notification, 'code');
    $reflection->setAccessible(true);

    return $reflection->getValue($notification);
}
