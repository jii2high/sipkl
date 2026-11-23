<?php

use App\Filament\App\Pages\PengaturanNotifikasi;
use App\Jobs\SendNotificationJob;
use App\Models\NotificationDeliveryFailure;
use App\Notifications\Notification as AppNotification;
use Filament\Facades\Filament;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config(['pkl.push.endpoint' => 'https://push.test/send']);
});

test('pengguna tanpa opt-in tidak menerima push', function () {
    Http::fake();

    $user = pklUser(['siswa']);

    (new SendNotificationJob($user, new AppNotification('Judul', 'Pesan')))->handle();

    Http::assertNothingSent();
});

test('pengguna opt-in menerima push tanpa mengganggu email dan in-app', function () {
    Http::fake();

    $user = pklUser(['siswa'], ['push_opt_in' => true]);

    (new SendNotificationJob($user, new AppNotification('Judul', 'Pesan', 'https://contoh.test/x')))->handle();

    Http::assertSent(fn ($request): bool => $request->url() === 'https://push.test/send'
        && $request['user_id'] === $user->getKey()
        && $request['payload']['judul'] === 'Judul');

    expect($user->notifications()->count())->toBe(1);
});

test('kegagalan push tercatat dan tidak melempar', function () {
    Http::fake(fn () => throw new ConnectionException('push mati'));

    $user = pklUser(['siswa'], ['push_opt_in' => true]);

    (new SendNotificationJob($user, new AppNotification('Judul', 'Pesan')))->handle();

    $failure = NotificationDeliveryFailure::query()->firstOrFail();

    expect($failure->channel)->toBe('push')
        ->and($failure->recipient_user_id)->toBe($user->getKey())
        ->and($user->notifications()->count())->toBe(1);
});

test('halaman notifikasi menyimpan pilihan opt-in dan opt-out', function () {
    Filament::setCurrentPanel(Filament::getPanel('app'));

    $user = pklUser(['siswa'], ['push_opt_in' => null]);
    $this->actingAs($user)->withSession(['active_role' => 'siswa']);

    Livewire\Livewire::test(PengaturanNotifikasi::class)
        ->assertSet('pushOptIn', null)
        ->call('setPush', true)
        ->assertSet('pushOptIn', true);

    expect($user->refresh()->push_opt_in)->toBeTrue();

    Livewire\Livewire::test(PengaturanNotifikasi::class)
        ->call('setPush', false)
        ->assertSet('pushOptIn', false);

    expect($user->refresh()->push_opt_in)->toBeFalse();
});
