<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['pengajuan_siswa_id', 'version_number', 'source_bimbingan_submission_id', 'file_path', 'is_legit', 'created_by'])]
class LaporanVersion extends Model
{
    public const UPDATED_AT = null;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['version_number' => 'integer', 'is_legit' => 'boolean'];
    }

    /** @return BelongsTo<PengajuanSiswa, $this> */
    public function pengajuanSiswa(): BelongsTo
    {
        return $this->belongsTo(PengajuanSiswa::class, 'pengajuan_siswa_id');
    }

    /** @return HasMany<DocumentSignature, $this> */
    public function signatures(): HasMany
    {
        return $this->hasMany(DocumentSignature::class);
    }
}
