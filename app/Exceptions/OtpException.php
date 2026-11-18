<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Pelanggaran aturan OTP first-login (BR-F1-2, BR-F1-3). Pesan Bahasa Indonesia
 * sudah spesifik; komponen Livewire menampilkannya sebagai galat field.
 */
class OtpException extends RuntimeException
{
    public static function invalid(): self
    {
        return new self(__('pkl.otp.invalid'));
    }

    public static function expired(): self
    {
        return new self(__('pkl.otp.expired'));
    }

    public static function tooManyAttempts(): self
    {
        return new self(__('pkl.otp.too_many_attempts'));
    }

    public static function resendCooldown(int $minutes): self
    {
        return new self(__('pkl.otp.resend_cooldown', ['minutes' => max(1, $minutes)]));
    }
}
