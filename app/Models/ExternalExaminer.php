<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['jurusan_id', 'nama', 'nik', 'contact_type', 'contact_value', 'contact_verified_at', 'created_by'])]
class ExternalExaminer extends Model
{
    public const UPDATED_AT = null;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['contact_verified_at' => 'datetime'];
    }

    /** @return BelongsTo<Jurusan, $this> */
    public function jurusan(): BelongsTo
    {
        return $this->belongsTo(Jurusan::class);
    }

    /** @return HasMany<JadwalSidang, $this> */
    public function jadwalSidang(): HasMany
    {
        return $this->hasMany(JadwalSidang::class);
    }
}
