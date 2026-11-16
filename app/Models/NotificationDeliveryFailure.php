<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * schema.sql Bagian 13 — kegagalan pengiriman notifikasi yang perlu ditangani Hubin.
 */
#[Fillable([
    'channel',
    'recipient_email',
    'recipient_user_id',
    'notification_type',
    'related_type',
    'related_id',
    'attempt_count',
    'failure_summary',
    'first_failed_at',
    'last_failed_at',
    'resolved_at',
])]
class NotificationDeliveryFailure extends Model
{
    public const UPDATED_AT = null;

    public $timestamps = false;

    protected $table = 'notification_delivery_failures';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'attempt_count' => 'integer',
            'first_failed_at' => 'datetime',
            'last_failed_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_user_id');
    }
}
