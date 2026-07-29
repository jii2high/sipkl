<?php

namespace App\Models;

use App\States\Pengajuan\PengajuanState;
use Database\Factories\PengajuanPklFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\ModelStates\HasStates;

class PengajuanPkl extends Model implements HasMedia
{
    /** @use HasFactory<PengajuanPklFactory> */
    use HasFactory, HasStates, InteractsWithMedia;

    protected $table = 'pengajuan_pkl';

    protected $fillable = [
        'siswa_id',
        'perusahaan_id',
        'periode_id',
        'status',
        'nomor_pengajuan',
        'kaprog_id',
        'catatan_kaprog',
        'verified_kaprog_at',
        'hubin_id',
        'surat_dikirim_at',
        'laporan_akhir_uploaded_at',
    ];

    protected $casts = [
        'status' => PengajuanState::class,
        'kaprog_id' => 'integer',
        'hubin_id' => 'integer',
        'verified_kaprog_at' => 'datetime',
        'surat_dikirim_at' => 'datetime',
        'laporan_akhir_uploaded_at' => 'datetime',
    ];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('surat_balasan_perusahaan')
            ->singleFile();

        $this->addMediaCollection('laporan_akhir_pkl')
            ->singleFile();

        $this->addMediaCollection('dokumen_pendukung')
            ->singleFile();
    }

    /** @return BelongsTo<SiswaProfile, $this> */
    public function siswa(): BelongsTo
    {
        return $this->belongsTo(SiswaProfile::class, 'siswa_id');
    }

    /** @return BelongsTo<Perusahaan, $this> */
    public function perusahaan(): BelongsTo
    {
        return $this->belongsTo(Perusahaan::class, 'perusahaan_id');
    }

    /** @return BelongsTo<PeriodePkl, $this> */
    public function periode(): BelongsTo
    {
        return $this->belongsTo(PeriodePkl::class, 'periode_id');
    }

    /** @return BelongsTo<User, $this> */
    public function kaprog(): BelongsTo
    {
        return $this->belongsTo(User::class, 'kaprog_id');
    }

    /** @return BelongsTo<User, $this> */
    public function hubin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'hubin_id');
    }

    /** @return HasMany<PengajuanPklHistory, $this> */
    public function histories(): HasMany
    {
        return $this->hasMany(PengajuanPklHistory::class, 'pengajuan_id');
    }
}
