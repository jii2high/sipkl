<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'form_submission_id',
    'jawaban_lama',
    'jawaban_baru',
    'reported_by',
    'reason',
    'approved_by_kaprog_id',
    'unlock_magic_link_id',
    'unlock_expires_at',
    'status',
])]
class NilaiSidangRevision extends Model
{
    public const UPDATED_AT = null;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'jawaban_lama' => 'array',
            'jawaban_baru' => 'array',
            'unlock_expires_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<FormSubmission, $this> */
    public function submission(): BelongsTo
    {
        return $this->belongsTo(FormSubmission::class, 'form_submission_id');
    }

    /** @return BelongsTo<MagicLink, $this> */
    public function unlockLink(): BelongsTo
    {
        return $this->belongsTo(MagicLink::class, 'unlock_magic_link_id');
    }

    /** @return BelongsTo<User, $this> */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_kaprog_id');
    }
}
