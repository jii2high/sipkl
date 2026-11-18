<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * Kegagalan pengiriman notifikasi yang permanen (BR-F14-2) agar tampak oleh Hubin.
 * Hanya metadata teknis yang disimpan; isi notifikasi dan rahasia tidak pernah masuk
 * ke tabel ini (BR-F14-4, TRD.md §12).
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
    public const CREATED_AT = null;

    public const UPDATED_AT = null;

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
}
