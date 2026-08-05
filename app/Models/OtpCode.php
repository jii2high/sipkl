<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OtpCode extends Model
{
    protected $fillable = ['user_id', 'code_hash', 'expires_at', 'attempts', 'verified_at'];

    protected $casts = ['expires_at' => 'datetime', 'verified_at' => 'datetime'];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // cek masa berlaku (10 menit)
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    // cek batas 5x percobaan
    public function isMaxAttempts(): bool
    {
        return $this->attempts >= 5;
    }
}
