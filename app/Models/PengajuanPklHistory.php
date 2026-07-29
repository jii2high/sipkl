<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $pengajuan_id
 * @property int $actor_id
 * @property string|null $status_dari
 * @property string $status_ke
 * @property string|null $catatan
 * @property string $created_at
 * @property-read PengajuanPkl|null $pengajuan
 * @property-read User|null $actor
 */
class PengajuanPklHistory extends Model
{
    protected $table = 'pengajuan_pkl_histories';

    public $timestamps = false;

    protected $fillable = [
        'pengajuan_id',
        'actor_id',
        'status_dari',
        'status_ke',
        'catatan',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    /** @return BelongsTo<PengajuanPkl, $this> */
    public function pengajuan(): BelongsTo
    {
        return $this->belongsTo(PengajuanPkl::class, 'pengajuan_id');
    }

    /** @return BelongsTo<User, $this> */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
