<?php

namespace App\Services;

use App\Exceptions\OtpException;
use App\Models\ExternalSession;
use App\Models\MagicLink;
use App\Models\User;
use App\Support\JakartaTime;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Token akses eksternal (TRD.md §9). Hanya SHA-256 token yang disimpan; satu
 * link aktif per (type, reference_id); revoke menutup semua sesi turunannya.
 * Token mentah dikembalikan sekali saat terbit dan tidak pernah di-log/audit.
 */
final class MagicLinkService
{
    public const TYPE_EXAMINER_SIDANG = 'examiner_sidang';

    public const TYPE_EXAMINER_UNLOCK = 'examiner_unlock';

    public const TYPE_SIGNER_STEP3 = 'signer_step3';

    public const TYPE_SIGNER_STEP4 = 'signer_step4';

    public const TYPE_INDUSTRY_ASSESSMENT = 'industry_assessment';

    public function __construct(private readonly AuditService $audit) {}

    /**
     * Terbitkan link baru. Link aktif sebelumnya untuk (type, reference) dicabut
     * dalam transaksi yang sama sehingga UNIQUE `(type, active_reference_id)` aman.
     *
     * @return array{link: MagicLink, token: string}
     */
    public function issue(
        string $type,
        int $referenceId,
        ?string $pin = null,
        ?User $createdBy = null,
        ?int $ttlMinutes = null,
        ?int $reissuedFromId = null,
    ): array {
        return DB::transaction(fn (): array => $this->createLink(
            $type, $referenceId, $pin, $createdBy, $ttlMinutes, $reissuedFromId,
        ));
    }

    /**
     * Terbitkan pengganti: revoke lama + terbit baru dalam satu transaksi
     * (BR-F11-1). Alasan wajib agar override Kaprog/Hubin tercatat di audit.
     *
     * @return array{link: MagicLink, token: string}
     */
    public function reissue(MagicLink $old, ?string $pin = null, ?User $actor = null, ?string $reason = null): array
    {
        $reason = $reason === null ? null : trim($reason);

        if ($actor !== null && ($reason === null || $reason === '')) {
            throw ValidationException::withMessages(['alasan' => __('pkl.eksternal.revoke_reason_required')]);
        }

        return DB::transaction(function () use ($old, $pin, $actor, $reason): array {
            $locked = MagicLink::query()->whereKey($old->getKey())->lockForUpdate()->firstOrFail();

            $this->close($locked, $actor, $reason ?? 'Digantikan tautan baru');

            if ($actor !== null) {
                $this->audit->log(
                    description: 'Magic link diterbitkan ulang',
                    subject: $locked,
                    event: 'magic_link_reissued',
                    properties: [
                        'tipe' => $locked->type,
                        'reference_id' => $locked->reference_id,
                        'alasan' => $reason,
                        'oleh' => $actor->getKey(),
                    ],
                    causer: $actor,
                );
            }

            return $this->createLink(
                (string) $locked->type,
                (int) $locked->reference_id,
                $pin,
                $actor,
                null,
                (int) $locked->getKey(),
            );
        });
    }

    /** Link aktif untuk (type, reference), bila ada. */
    public function activeFor(string $type, int $referenceId): ?MagicLink
    {
        return MagicLink::query()
            ->where('type', $type)
            ->where('reference_id', $referenceId)
            ->where('is_active', true)
            ->first();
    }

    /** Link hanya sah bila aktif, belum dipakai, belum dicabut, dan belum kedaluwarsa. */
    public function resolve(string $token): ?MagicLink
    {
        $link = MagicLink::query()->where('token_hash', $this->hash($token))->first();

        return $link !== null && $this->isUsable($link) ? $link : null;
    }

    public function isUsable(MagicLink $link): bool
    {
        return $link->is_active
            && $link->used_at === null
            && $link->revoked_at === null
            && $link->expires_at !== null
            && $link->expires_at->isFuture();
    }

    /** Tandai token terpakai (single-use); sesi turunannya tetap berlaku. */
    public function consume(MagicLink $link, ?string $fingerprint = null): void
    {
        $link->newQuery()->whereKey($link->getKey())->whereNull('used_at')->update([
            'used_at' => now(),
            'is_active' => false,
            'device_fingerprint' => $fingerprint ?? $link->device_fingerprint,
        ]);

        $link->refresh();
    }

    /**
     * Cabut link dan tutup semua sesi turunannya (BR-F11-3). Override oleh pengguna
     * internal wajib menyertakan alasan yang ikut tercatat di audit.
     */
    public function revoke(MagicLink $link, ?User $actor = null, ?string $reason = null): void
    {
        if ($actor !== null && ($reason === null || trim($reason) === '')) {
            throw ValidationException::withMessages(['alasan' => __('pkl.eksternal.revoke_reason_required')]);
        }

        DB::transaction(function () use ($link, $actor, $reason): void {
            $locked = MagicLink::query()->whereKey($link->getKey())->lockForUpdate()->firstOrFail();

            $this->close($locked, $actor, $reason);

            if ($actor !== null) {
                $this->audit->log(
                    description: 'Magic link dicabut manual',
                    subject: $locked,
                    event: 'magic_link_revoked',
                    properties: [
                        'tipe' => $locked->type,
                        'reference_id' => $locked->reference_id,
                        'alasan' => $reason,
                        'oleh' => $actor->getKey(),
                    ],
                    causer: $actor,
                );
            }
        });
    }

    /** Kedaluwarsa oleh job: tutup link dan sesi turunannya tanpa audit manual. */
    public function expire(MagicLink $link): void
    {
        DB::transaction(fn () => $this->close($link, null, 'Kedaluwarsa'));
    }

    /** PIN penguji 4 digit; tiga kali salah membuat link tidak berlaku (TRD.md §9.2). */
    public function verifyPin(MagicLink $link, string $pin): void
    {
        $max = (int) config('pkl.examiner.pin_max_attempts');

        if ($link->pin_hash === null) {
            throw OtpException::invalid();
        }

        if ($link->pin_attempt_count >= $max) {
            throw OtpException::tooManyAttempts();
        }

        $link->increment('pin_attempt_count');

        if (! hash_equals((string) $link->pin_hash, $this->hash($pin))) {
            if ($link->pin_attempt_count >= $max) {
                $this->close($link, null, 'PIN salah '.$max.' kali');
            }

            throw OtpException::invalid();
        }

        $link->newQuery()->whereKey($link->getKey())->update(['pin_attempt_count' => 0]);
    }

    /**
     * Ikat perangkat pada pemakaian pertama. Perangkat berbeda tidak ditolak keras,
     * tetapi pemanggil wajib meminta verifikasi OTP/PIN ulang (TRD.md §9.4).
     */
    public function bindDevice(MagicLink $link, string $fingerprint): bool
    {
        if ($link->device_fingerprint === null) {
            $link->update(['device_fingerprint' => $fingerprint]);

            return true;
        }

        return hash_equals((string) $link->device_fingerprint, $fingerprint);
    }

    public function fingerprint(Request $request): string
    {
        $ip = explode('.', (string) $request->ip());

        return hash('sha256', $request->userAgent().'|'.implode('.', array_slice($ip, 0, 2)));
    }

    public function hash(string $value): string
    {
        return hash('sha256', $value);
    }

    /** Token 32 byte acak dalam base64url tanpa padding. */
    private function randomToken(): string
    {
        $bytes = random_bytes(max(32, (int) config('pkl.magic_link.min_bytes')));

        return rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');
    }

    /**
     * @return array{link: MagicLink, token: string}
     */
    private function createLink(
        string $type,
        int $referenceId,
        ?string $pin,
        ?User $createdBy,
        ?int $ttlMinutes,
        ?int $reissuedFromId,
    ): array {
        MagicLink::query()
            ->where('type', $type)
            ->where('reference_id', $referenceId)
            ->where('is_active', true)
            ->lockForUpdate()
            ->get()
            ->each(fn (MagicLink $existing) => $this->close($existing, null, 'Digantikan tautan baru'));

        $token = $this->randomToken();
        $expiresAt = $ttlMinutes === null
            ? $this->defaultExpiry($type)
            : now()->addMinutes(max(1, $ttlMinutes));

        $link = MagicLink::query()->create([
            'token_hash' => $this->hash($token),
            'type' => $type,
            'reference_id' => $referenceId,
            'pin_hash' => $pin === null ? null : $this->hash($pin),
            'expires_at' => $expiresAt,
            'reissued_from_id' => $reissuedFromId,
            'created_by' => $createdBy?->getKey(),
            'is_active' => true,
        ]);

        return ['link' => $link, 'token' => $token];
    }

    private function defaultExpiry(string $type): CarbonImmutable
    {
        return match ($type) {
            self::TYPE_EXAMINER_SIDANG => JakartaTime::endOfDay(JakartaTime::today()),
            self::TYPE_EXAMINER_UNLOCK => JakartaTime::now()->addHours(24),
            default => JakartaTime::now()->addDays((int) config('pkl.magic_link.ttl_days')),
        };
    }

    private function close(MagicLink $link, ?User $actor, ?string $reason): void
    {
        $link->newQuery()->whereKey($link->getKey())->whereNull('revoked_at')->update([
            'is_active' => false,
            'revoked_at' => now(),
            'revoked_by' => $actor?->getKey(),
            'revoke_reason' => $reason,
        ]);

        ExternalSession::query()
            ->where('magic_link_id', $link->getKey())
            ->whereNull('revoked_at')
            ->update(['revoked_at' => now()]);
    }
}
