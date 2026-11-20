<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'token_hash',
    'type',
    'reference_id',
    'pin_hash',
    'pin_attempt_count',
    'device_fingerprint',
    'expires_at',
    'used_at',
    'revoked_at',
    'revoked_by',
    'revoke_reason',
    'reissued_from_id',
    'created_by',
    'is_active',
])]
class MagicLink extends Model
{
    public const UPDATED_AT = null;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'pin_attempt_count' => 'integer',
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
            'revoked_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    /** @return HasMany<ExternalSession, $this> */
    public function sessions(): HasMany
    {
        return $this->hasMany(ExternalSession::class);
    }
}
