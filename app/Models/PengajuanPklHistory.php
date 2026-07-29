<?php

namespace App\Models;

use Database\Factories\PengajuanPklHistoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PengajuanPklHistory extends Model
{
    /** @use HasFactory<PengajuanPklHistoryFactory> */
    use HasFactory;

    protected $table = 'pengajuan_pkl_histories';

    const UPDATED_AT = null;

    protected $fillable = [
        'pengajuan_id',
        'actor_id',
        'status_dari',
        'status_ke',
        'catatan',
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
