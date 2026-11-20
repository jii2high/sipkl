<?php

use App\Exceptions\OtpException;
use App\Jobs\ExpireMagicLinksJob;
use App\Livewire\Eksternal\RequestNewLink;
use App\Models\ExternalSession;
use App\Models\MagicLink;
use App\Notifications\ExternalOtpNotification;
use App\Services\ExternalOtpService;
use App\Services\MagicLinkService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;

beforeEach(function () {
    // Rute uji: perilaku guard yang sama dengan rute bertoken T6.3.
    Route::middleware(['web', 'eksternal'])->get('/eksternal/_uji', fn () => response('ok'));
});

/** @return array{link: MagicLink, token: string} */
function eksternalLink(string $type = MagicLinkService::TYPE_SIGNER_STEP3, int $reference = 42): array
{
    return app(MagicLinkService::class)->issue($type, $reference, null, pklUser(['hubin']));
}

function eksternalSession(array $link): array
{
    Notification::fake();
    $code = app(ExternalOtpService::class)->issue($link['link'], 'signer@contoh.test');
    $session = ExternalSession::query()->latest('id')->firstOrFail();

    app(ExternalOtpService::class)->verify($session, $code);

    return [$session->refresh(), $code];
}

test('menerbitkan link kedua mencabut link pertama dan hanya satu yang aktif', function () {
    $first = eksternalLink();
    $second = app(MagicLinkService::class)->reissue($first['link'], null, pklUser(['hubin']), 'Pengganti untuk penguji.');

    expect($first['link']->refresh()->is_active)->toBeFalse()
        ->and($first['link']->revoked_at)->not->toBeNull()
        ->and(app(MagicLinkService::class)->resolve($first['token']))->toBeNull()
        ->and(app(MagicLinkService::class)->resolve($second['token'])?->getKey())->toBe($second['link']->getKey())
        ->and($second['link']->reissued_from_id)->toBe($first['link']->getKey())
        ->and(MagicLink::query()->where('is_active', true)->count())->toBe(1);
});

test('mencabut link menutup sesi turunannya dan guard menolak', function () {
    $link = eksternalLink();
    [$session] = eksternalSession($link);

    app(MagicLinkService::class)->revoke($link['link'], pklUser(['hubin']), 'Pengganti diminta penguji.');

    expect($session->refresh()->revoked_at)->not->toBeNull();

    $this->withSession(['external_session_id' => $session->getKey()])
        ->get('/eksternal/_uji')
        ->assertStatus(410);
});

test('token mentah tidak pernah tersimpan di basis data', function () {
    $issued = eksternalLink();

    expect(MagicLink::query()->where('token_hash', $issued['token'])->exists())->toBeFalse()
        ->and(MagicLink::query()->where('token_hash', hash('sha256', $issued['token']))->exists())->toBeTrue()
        ->and(DB::table('magic_links')->pluck('token_hash'))->not->toContain($issued['token']);
});

test('link tidak ada, tidak valid, dan kedaluwarsa menghasilkan pesan yang sama', function () {
    $missing = $this->get('/eksternal/_uji');

    $invalid = $this->withSession(['external_session_id' => 999999])->get('/eksternal/_uji');

    $link = eksternalLink();
    [$session] = eksternalSession($link);
    $link['link']->update(['expires_at' => now()->subMinute()]);
    $expired = $this->withSession(['external_session_id' => $session->getKey()])->get('/eksternal/_uji');

    $expected = __('pkl.eksternal.invalid_link');

    foreach ([$missing, $invalid, $expired] as $response) {
        expect($response->getStatusCode())->toBe(410)
            ->and($response->getContent())->toContain($expected);
    }

    expect($missing->getContent())->toBe($invalid->getContent())
        ->and($invalid->getContent())->toBe($expired->getContent());
});

test('otp eksternal kedaluwarsa lima menit, maksimal tiga percobaan, sesi 24 jam', function () {
    Notification::fake();

    $link = eksternalLink();
    app(ExternalOtpService::class)->issue($link['link'], 'signer@contoh.test');
    $session = ExternalSession::query()->latest('id')->firstOrFail();

    Notification::assertSentOnDemand(ExternalOtpNotification::class);

    for ($attempt = 1; $attempt <= 3; $attempt++) {
        expect(fn () => app(ExternalOtpService::class)->verify($session->refresh(), '000000'))
            ->toThrow(OtpException::class);
    }

    expect(fn () => app(ExternalOtpService::class)->verify($session->refresh(), '000000'))
        ->toThrow(OtpException::class);

    $freshCode = app(ExternalOtpService::class)->issue($link['link'], 'signer@contoh.test');
    $fresh = ExternalSession::query()->latest('id')->firstOrFail();
    app(ExternalOtpService::class)->verify($fresh, $freshCode);

    expect($fresh->refresh()->session_expires_at->greaterThan(now()->addHours(23)))->toBeTrue();

    $expired = ExternalSession::query()->create([
        'magic_link_id' => $link['link']->getKey(),
        'otp_code_hash' => hash('sha256', '123456'),
        'otp_expires_at' => now()->subMinute(),
    ]);

    expect(fn () => app(ExternalOtpService::class)->verify($expired, '123456'))
        ->toThrow(OtpException::class);
});

test('job kedaluwarsa bersifat idempoten dan halaman minta link selalu netral', function () {
    $link = eksternalLink();
    $link['link']->update(['expires_at' => now()->subMinute()]);

    app(ExpireMagicLinksJob::class)->handle(app(MagicLinkService::class));
    app(ExpireMagicLinksJob::class)->handle(app(MagicLinkService::class));

    expect($link['link']->refresh()->is_active)->toBeFalse()
        ->and($link['link']->revoked_at)->not->toBeNull();

    $hubin = pklUser(['hubin']);
    Notification::fake();

    Livewire::test(RequestNewLink::class)
        ->set('email', 'tidak-terdaftar@contoh.test')
        ->call('submit')
        ->assertSet('submitted', true)
        ->assertSee('Permintaan Anda sudah diteruskan ke Hubin.');

    Notification::assertSentTo($hubin, App\Notifications\Notification::class);
});
