<?php

namespace App\Services;

use App\Mail\OtpMail;
use App\Models\OtpCode;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;

final class OtpService
{
    private const int CODE_LIFETIME_SECONDS = 60;

    private const int MAX_ATTEMPTS = 3;

    public function __construct(
        private readonly NotificationDispatcher $notifications,
    ) {}

    public function issue(User $user): OtpCode
    {
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $otp = DB::transaction(function () use ($user, $code): OtpCode {
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->getKey());

            if (! $lockedUser->is_active || ! $lockedUser->is_first_login) {
                throw new DomainException('OTP hanya dapat diterbitkan untuk akun aktif yang belum menyelesaikan first-login.');
            }

            $now = now();

            $lockedUser->otpCodes()
                ->whereNull('invalidated_at')
                ->whereNull('verified_at')
                ->update(['invalidated_at' => $now]);

            return $lockedUser->otpCodes()->create([
                'code_hash' => hash('sha256', $code),
                'purpose' => 'first_login',
                'attempt_count' => 0,
                'expires_at' => $now->copy()->addSeconds(self::CODE_LIFETIME_SECONDS),
            ]);
        });

        $this->notifications->dispatch($user, new OtpMail($code));

        return $otp;
    }

    public function verify(User $user, string $code): bool
    {
        return DB::transaction(function () use ($user, $code): bool {
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->getKey());

            $otp = $lockedUser->otpCodes()
                ->where('purpose', 'first_login')
                ->whereNull('invalidated_at')
                ->whereNull('verified_at')
                ->latest('id')
                ->lockForUpdate()
                ->first();

            if ($otp === null) {
                return false;
            }

            $now = now();

            if ($otp->expires_at->lessThanOrEqualTo($now)) {
                $otp->update(['invalidated_at' => $now]);

                return false;
            }

            if ($otp->attempt_count >= self::MAX_ATTEMPTS) {
                $otp->update(['invalidated_at' => $now]);

                return false;
            }

            $otp->attempt_count++;

            if (preg_match('/^\d{6}$/D', $code) === 1
                && hash_equals($otp->code_hash, hash('sha256', $code))) {
                $otp->verified_at = $now;
                $otp->save();

                return true;
            }

            $otp->save();

            return false;
        });
    }
}
