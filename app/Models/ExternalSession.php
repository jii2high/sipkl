<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['magic_link_id', 'otp_code_hash', 'otp_expires_at', 'otp_attempt_count', 'verified_at', 'session_expires_at', 'revoked_at'])]
class ExternalSession extends Model
{
    public const UPDATED_AT = null;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'otp_attempt_count' => 'integer',
            'otp_expires_at' => 'datetime',
            'verified_at' => 'datetime',
            'session_expires_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<MagicLink, $this> */
    public function magicLink(): BelongsTo
    {
        return $this->belongsTo(MagicLink::class);
    }
}
