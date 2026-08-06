<?php

namespace App\Models;

use Database\Factories\PengajuanPklFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

class PengajuanPkl extends Model
{
    /** @use HasFactory<PengajuanPklFactory> */
    use HasFactory;

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
        'laporan_akhir_path',
    ];

    protected $appends = ['exists_surat_pengantar'];

    protected $casts = [
        'status' => 'string',
        'kaprog_id' => 'integer',
        'hubin_id' => 'integer',
        'verified_kaprog_at' => 'datetime',
        'surat_dikirim_at' => 'datetime',
        'laporan_akhir_uploaded_at' => 'datetime',
    ];

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

    /** @return HasMany<PenugasanPembimbing, $this> */
    public function penugasans(): HasMany
    {
        return $this->hasMany(PenugasanPembimbing::class, 'pengajuan_id');
    }

    /** @return HasOne<PenugasanPembimbing, $this> */
    public function penugasanAktif(): HasOne
    {
        return $this->hasOne(PenugasanPembimbing::class, 'pengajuan_id')
            ->where('status', 'aktif')
            ->latestOfMany();
    }

    /** @return BelongsToMany<SuratPengantar, $this> */
    public function suratPengantars(): BelongsToMany
    {
        return $this->belongsToMany(SuratPengantar::class, 'surat_pengantar_pengajuan', 'pengajuan_id', 'surat_pengantar_id');
    }

    /** @return HasOne<SuratBalasan, $this> */
    public function suratBalasan(): HasOne
    {
        return $this->hasOne(SuratBalasan::class, 'pengajuan_id');
    }

    /** @return HasOneThrough<FormulirPenilaian, PenugasanPembimbing, $this> */
    public function formulirPenilaian(): HasOneThrough
    {
        return $this->hasOneThrough(
            FormulirPenilaian::class,
            PenugasanPembimbing::class,
            'pengajuan_id', // FK di penugasan_pembimbing
            'penugasan_id', // FK di formulir_penilaian
            'id',
            'id'
        );
    }

    public function getLaporanAkhirUploadedAttribute(): bool
    {
        return $this->laporan_akhir_uploaded_at !== null;
    }

    public function getExistsSuratPengantarAttribute(): bool
    {
        return $this->suratPengantars()->exists();
    }
}
