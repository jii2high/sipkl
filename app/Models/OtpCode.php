<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['user_id', 'code_hash', 'purpose', 'attempt_count', 'expires_at', 'invalidated_at', 'verified_at'])]
class OtpCode extends Model
{
    public const UPDATED_AT = null;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'attempt_count' => 'integer',
            'expires_at' => 'datetime',
            'invalidated_at' => 'datetime',
            'verified_at' => 'datetime',
        ];
    }
}
