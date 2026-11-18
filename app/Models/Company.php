<?php

namespace App\Models;

use Database\Factories\CompanyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'nama',
    'alamat',
    'email',
    'website',
    'telp_fax',
    'cp_name',
    'cp_phone',
    'pembimbing_industri_nama',
    'pembimbing_industri_email',
    'pembimbing_industri_email_verified_at',
    'kepala_industri_nama',
    'kepala_industri_email',
    'kepala_industri_email_verified_at',
    'source',
    'created_by',
    'merged_into_id',
])]
class Company extends Model
{
    /** @use HasFactory<CompanyFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'pembimbing_industri_email_verified_at' => 'datetime',
            'kepala_industri_email_verified_at' => 'datetime',
        ];
    }

    /**
     * Ganti nama/email signer = verifikasi lama gugur (TASKS M2.8).
     */
    protected static function booted(): void
    {
        static::updating(function (self $company): void {
            if (
                $company->isDirty(['pembimbing_industri_nama', 'pembimbing_industri_email'])
                && ! $company->isDirty('pembimbing_industri_email_verified_at')
            ) {
                $company->pembimbing_industri_email_verified_at = null;
            }

            if (
                $company->isDirty(['kepala_industri_nama', 'kepala_industri_email'])
                && ! $company->isDirty('kepala_industri_email_verified_at')
            ) {
                $company->kepala_industri_email_verified_at = null;
            }
        });
    }

    /**
     * Signer siap dipakai menandatangani: nama + email terisi dan email terverifikasi.
     */
    public function signerPembimbingSiap(): bool
    {
        return filled($this->pembimbing_industri_nama)
            && filled($this->pembimbing_industri_email)
            && $this->pembimbing_industri_email_verified_at !== null;
    }

    public function signerKepalaSiap(): bool
    {
        return filled($this->kepala_industri_nama)
            && filled($this->kepala_industri_email)
            && $this->kepala_industri_email_verified_at !== null;
    }

    /**
     * Perusahaan yang belum di-merge ke perusahaan lain (kandidat aktif).
     *
     * @param  Builder<Company>  $query
     * @return Builder<Company>
     */
    public function scopeBelumMerge(Builder $query): Builder
    {
        return $query->whereNull('merged_into_id');
    }

    public function sudahMerge(): bool
    {
        return $this->merged_into_id !== null;
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return BelongsTo<Company, $this>
     */
    public function mergedInto(): BelongsTo
    {
        return $this->belongsTo(self::class, 'merged_into_id');
    }

    /**
     * @return HasMany<Company, $this>
     */
    public function mergedChildren(): HasMany
    {
        return $this->hasMany(self::class, 'merged_into_id');
    }
}
