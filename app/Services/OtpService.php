<?php

namespace App\Services;

use App\Exceptions\OtpException;
use App\Models\OtpCode;
use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;

/**
 * OTP internal first-login (TRD.md §7.1, BR-F1-2/3). Kode 6 digit disimpan
 * ter-hash (SHA-256, tanpa plaintext); TTL 60 detik; maksimum 3 percobaan verify
 * (percobaan ke-4 menginvalidasi); resend dibatasi limiter terpisah `otp-resend`.
 */
final class OtpService
{
    public const PURPOSE_FIRST_LOGIN = 'first_login';

    /**
     * Terbitkan kode OTP baru dan kembalikan kode plaintext untuk dikirim lewat
     * email. Kode plaintext tidak pernah disimpan di database atau log.
     */
    public function issue(User $user, string $purpose = self::PURPOSE_FIRST_LOGIN): string
    {
        $this->assertResendAllowed($user, $purpose);

        // OTP aktif sebelumnya dibatalkan: satu kode berlaku pada satu waktu.
        $user->otpCodes()
            ->where('purpose', $purpose)
            ->whereNull('verified_at')
            ->whereNull('invalidated_at')
            ->update(['invalidated_at' => now()]);

        $length = (int) config('pkl.otp.internal.length');
        $code = str_pad((string) random_int(0, 10 ** $length - 1), $length, '0', STR_PAD_LEFT);

        $user->otpCodes()->create([
            'code_hash' => $this->hash($code),
            'purpose' => $purpose,
            'expires_at' => now()->addSeconds((int) config('pkl.otp.internal.ttl_seconds')),
        ]);

        return $code;
    }

    /**
     * Verifikasi kode OTP. Limiter `otp-verify` membatasi jumlah percobaan; percobaan
     * yang melampaui batas menginvalidasi OTP dan menolak (BR-F1-2).
     */
    public function verify(User $user, string $code, string $purpose = self::PURPOSE_FIRST_LOGIN): OtpCode
    {
        $otp = $this->latestActive($user, $purpose);

        if ($otp === null) {
            throw OtpException::tooManyAttempts();
        }

        if ($otp->expires_at->isPast()) {
            $otp->update(['invalidated_at' => now()]);

            throw OtpException::expired();
        }

        $key = $this->verifyKey($user, $purpose);
        $max = (int) config('pkl.otp.internal.max_verify_attempts');

        if (RateLimiter::tooManyAttempts($key, $max)) {
            $otp->update(['invalidated_at' => now()]);

            throw OtpException::tooManyAttempts();
        }

        RateLimiter::hit($key, (int) config('pkl.otp.internal.ttl_seconds'));
        $otp->increment('attempt_count');

        if (! hash_equals($otp->code_hash, $this->hash($code))) {
            throw OtpException::invalid();
        }

        // Sekali pakai: kode yang sudah terverifikasi tidak dapat dipakai lagi.
        $otp->update(['verified_at' => now(), 'invalidated_at' => now()]);
        RateLimiter::clear($key);

        return $otp;
    }

    private function latestActive(User $user, string $purpose): ?OtpCode
    {
        return $user->otpCodes()
            ->where('purpose', $purpose)
            ->whereNull('verified_at')
            ->whereNull('invalidated_at')
            ->latest('id')
            ->first();
    }

    private function assertResendAllowed(User $user, string $purpose): void
    {
        $key = "otp-resend:{$purpose}:{$user->getKey()}";
        // Satu kirim awal + `resend_max` kirim ulang per jendela (BR-F1-3).
        $max = (int) config('pkl.otp.internal.resend_max') + 1;
        $window = (int) config('pkl.otp.internal.resend_window_minutes') * 60;

        if (RateLimiter::tooManyAttempts($key, $max)) {
            throw OtpException::resendCooldown((int) ceil(RateLimiter::availableIn($key) / 60));
        }

        RateLimiter::hit($key, $window);
    }

    private function verifyKey(User $user, string $purpose): string
    {
        return "otp-verify:{$purpose}:{$user->getKey()}";
    }

    private function hash(string $code): string
    {
        return hash('sha256', $code);
    }
}
