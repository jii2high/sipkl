<?php

namespace App\Models;

use Database\Factories\PenugasanPembimbingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property-read User $guruPembimbing
 * @property-read PengajuanPkl $pengajuan
 * @property-read User $assignedBy
 */
class PenugasanPembimbing extends Model
{
    /** @use HasFactory<PenugasanPembimbingFactory> */
    use HasFactory;

    protected $table = 'penugasan_pembimbing';

    protected $fillable = [
        'guru_pembimbing_id',
        'pengajuan_id',
        'assigned_by',
        'status',
        'ended_at',
    ];

    protected function casts(): array
    {
        return [
            'ended_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function guruPembimbing(): BelongsTo
    {
        return $this->belongsTo(User::class, 'guru_pembimbing_id');
    }

    /** @return BelongsTo<PengajuanPkl, $this> */
    public function pengajuan(): BelongsTo
    {
        return $this->belongsTo(PengajuanPkl::class, 'pengajuan_id');
    }

    /** @return BelongsTo<User, $this> */
    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    /** @return HasMany<KunjunganMonitoring, $this> */
    public function kunjunganMonitorings(): HasMany
    {
        return $this->hasMany(KunjunganMonitoring::class, 'penugasan_id');
    }

    /** @return HasOne<FormulirPenilaian, $this> */
    public function formulirPenilaian(): HasOne
    {
        return $this->hasOne(FormulirPenilaian::class, 'penugasan_id');
    }
}
