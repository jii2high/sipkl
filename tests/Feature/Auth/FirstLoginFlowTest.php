<?php

use App\Jobs\SendEmailNotificationJob;
use App\Livewire\Public\ForcePasswordReset;
use App\Livewire\Public\VerifyOtp;
use App\Mail\OtpMail;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('memisahkan rate limiter otp resend dan verify', function () {
    $user = User::factory()->create();

    for ($attempt = 0; $attempt < 5; $attempt++) {
        app(OtpService::class)->resend($user);
    }

    expect(fn () => app(OtpService::class)->resend($user))->toThrow(DomainException::class)
        ->and(app(OtpService::class)->verify($user, '000000'))->toBeFalse();
});

it('memverifikasi OTP dan mengharuskan reset password pada first login', function () {
    Queue::fake();
    $user = User::factory()->create();
    $service = app(OtpService::class);
    $service->issue($user);

    $job = Queue::pushed(SendEmailNotificationJob::class)->first();
    $code = (new ReflectionProperty($job->notification, 'code'));
    $code->setAccessible(true);
    $otpCode = $code->getValue($job->notification);

    session(['otp_email' => $user->email, 'otp_pending' => true]);

    Livewire::test(VerifyOtp::class)
        ->set('code', $otpCode)
        ->call('submit')
        ->assertRedirect(route('force-password-reset'));

    $user->refresh();

    expect($user->email_verified_at)->not->toBeNull()
        ->and($user->is_first_login)->toBeTrue();

    session(['password_reset_user_id' => $user->id, 'password_reset_pending' => true]);

    Livewire::test(ForcePasswordReset::class)
        ->set('password', 'passwordbaru123')
        ->set('password_confirmation', 'passwordbaru123')
        ->call('submit')
        ->assertRedirect('/app');

    $this->assertTrue(Auth::check());
    $user->refresh();
    expect($user->is_first_login)->toBeFalse()
        ->and(Auth::id())->toBe($user->id);
});
