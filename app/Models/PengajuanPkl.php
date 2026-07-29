<?php

namespace App\Models;

use App\States\Pengajuan\PengajuanState;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\ModelStates\HasStates;

/**
 * @property int $id
 * @property int $siswa_id
 * @property int $perusahaan_id
 * @property int $periode_id
 * @property string $status
 * @property int|null $kaprog_id
 * @property string|null $catatan_kaprog
 * @property string|null $verified_kaprog_at
 * @property int|null $hubin_id
 * @property string|null $surat_dikirim_at
 * @property-read PengajuanState $status
 * @property-read SiswaProfile|null $siswa
 * @property-read Perusahaan|null $perusahaan
 * @property-read PeriodePkl|null $periode
 * @property-read PengajuanPklHistory[] $histories
 * @property-read User|null $kaprog
 */
class PengajuanPkl extends Model
{
    use HasStates;

    protected $table = 'pengajuan_pkl';

    protected $fillable = [
        'siswa_id',
        'perusahaan_id',
        'periode_id',
        'catatan_kaprog',
    ];

    protected $casts = [
        'status' => PengajuanState::class,
        'verified_kaprog_at' => 'datetime',
        'surat_dikirim_at' => 'datetime',
    ];

    /** @return BelongsTo<SiswaProfile, $this> */
    public function siswa(): BelongsTo
    {
        return $this->belongsTo(SiswaProfile::class, 'siswa_id');
    }

    /** @return BelongsTo<Perusahaan, $this> */
    public function perusahaan(): BelongsTo
    {
        return $this->belongsTo(Perusahaan::class);
    }

    /** @return BelongsTo<PeriodePkl, $this> */
    public function periode(): BelongsTo
    {
        return $this->belongsTo(PeriodePkl::class, 'periode_id');
    }

    /** @return HasMany<PengajuanPklHistory, $this> */
    public function histories(): HasMany
    {
        return $this->hasMany(PengajuanPklHistory::class, 'pengajuan_id');
    }

    /** @return BelongsTo<User, $this> */
    public function kaprog(): BelongsTo
    {
        return $this->belongsTo(User::class, 'kaprog_id');
    }
}
