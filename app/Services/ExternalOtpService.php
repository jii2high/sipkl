<?php

namespace App\Services;

use App\Exceptions\OtpException;
use App\Models\ExternalSession;
use App\Models\MagicLink;
use App\Notifications\ExternalOtpNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

/**
 * OTP email untuk portal eksternal (TRD.md §9.2, BR-F11-5): 6 digit, berlaku
 * 5 menit, maksimum 3 percobaan, dan sesi 24 jam setelah verifikasi. Kode hanya
 * disimpan sebagai hash.
 */
final class ExternalOtpService
{
    /** Terbitkan kode baru untuk link yang sah lalu kirim lewat email. */
    public function issue(MagicLink $link, string $email): string
    {
        if (! app(MagicLinkService::class)->isUsable($link)) {
            throw OtpException::expired();
        }

        $length = (int) config('pkl.otp.external.length');
        $ttl = (int) config('pkl.otp.external.ttl_minutes');
        $code = str_pad((string) random_int(0, 10 ** $length - 1), $length, '0', STR_PAD_LEFT);

        ExternalSession::query()->create([
            'magic_link_id' => $link->getKey(),
            'otp_code_hash' => hash('sha256', $code),
            'otp_expires_at' => now()->addMinutes($ttl),
        ]);

        Notification::route('mail', $email)->notify(new ExternalOtpNotification($code, $ttl));

        return $code;
    }

    /** Verifikasi sekali pakai; sesi 24 jam dihitung dari waktu verifikasi. */
    public function verify(ExternalSession $session, string $code): ExternalSession
    {
        return DB::transaction(function () use ($session, $code): ExternalSession {
            $locked = ExternalSession::query()->whereKey($session->getKey())->lockForUpdate()->firstOrFail();

            if ($locked->revoked_at !== null || $locked->verified_at !== null) {
                throw OtpException::invalid();
            }

            if ($locked->otp_expires_at->isPast()) {
                throw OtpException::expired();
            }

            $max = (int) config('pkl.otp.external.max_attempts');

            if ($locked->otp_attempt_count >= $max) {
                throw OtpException::tooManyAttempts();
            }

            $locked->increment('otp_attempt_count');

            if (! hash_equals((string) $locked->otp_code_hash, hash('sha256', $code))) {
                throw OtpException::invalid();
            }

            $locked->update([
                'verified_at' => now(),
                'session_expires_at' => now()->addHours((int) config('pkl.otp.external.session_hours')),
            ]);

            return $locked->refresh();
        });
    }

    /**
     * Sesi boleh dipakai bila sudah terverifikasi, belum kedaluwarsa/dicabut, dan
     * link induknya belum dicabut atau kedaluwarsa.
     */
    public function isUsable(ExternalSession $session): bool
    {
        if ($session->verified_at === null
            || $session->session_expires_at === null
            || $session->session_expires_at->isPast()
            || $session->revoked_at !== null) {
            return false;
        }

        $link = $session->magicLink;

        return $link !== null
            && $link->revoked_at === null
            && $link->expires_at !== null
            && $link->expires_at->isFuture();
    }
}
