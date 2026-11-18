<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Activity;

/**
 * Penulisan audit event (spatie/laravel-activitylog) dengan redaksi rahasia.
 * Token, PIN, OTP, dan password tidak pernah masuk activity_log (TRD.md §12).
 */
final class AuditService
{
    public const REDACTED = '[disunting]';

    /** @var array<int, string> Potongan nama kunci yang dianggap rahasia. */
    private const SENSITIVE_KEY_PARTS = [
        'password',
        'token',
        'pin',
        'otp',
        'secret',
        'remember',
    ];

    /**
     * @param  array<string, mixed>  $properties
     */
    public function log(
        string $description,
        ?Model $subject = null,
        ?string $event = null,
        array $properties = [],
        ?string $logName = null,
        ?Model $causer = null,
    ): Activity {
        $logger = activity($logName)->event($event)->withProperties(self::redact($properties));

        if ($subject !== null) {
            $logger->performedOn($subject);
        }

        if ($causer !== null) {
            $logger->causedBy($causer);
        }

        return $logger->log($description);
    }

    /**
     * Ganti nilai pada kunci sensitif (rekursif) agar tidak ada rahasia yang terlog.
     *
     * @param  array<array-key, mixed>  $properties
     * @return array<array-key, mixed>
     */
    public static function redact(array $properties): array
    {
        $redacted = [];

        foreach ($properties as $key => $value) {
            if (is_string($key) && self::isSensitive($key)) {
                $redacted[$key] = self::REDACTED;

                continue;
            }

            $redacted[$key] = is_array($value) ? self::redact($value) : $value;
        }

        return $redacted;
    }

    private static function isSensitive(string $key): bool
    {
        $key = strtolower($key);

        foreach (self::SENSITIVE_KEY_PARTS as $part) {
            if (str_contains($key, $part)) {
                return true;
            }
        }

        return false;
    }
}
