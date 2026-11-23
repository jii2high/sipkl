<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

#[Fillable([
    'jurusan_id',
    'academic_period_id',
    'form_type',
    'nama',
    'has_kehadiran',
    'has_catatan',
    'is_published',
    'published_at',
    'created_by',
])]
class FormDefinition extends Model
{
    public const UPDATED_AT = null;

    public const TYPE_SIDANG = 'sidang';

    public const TYPE_INDUSTRI = 'penilaian_industri';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'has_kehadiran' => 'boolean',
            'has_catatan' => 'boolean',
            'is_published' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    /** @return HasMany<FormSection, $this> */
    public function sections(): HasMany
    {
        return $this->hasMany(FormSection::class)->orderBy('sort_order');
    }

    /** @return HasManyThrough<FormItem, FormSection, $this> */
    public function items(): HasManyThrough
    {
        return $this->hasManyThrough(
            FormItem::class,
            FormSection::class,
            'form_definition_id',
            'form_section_id',
        );
    }

    /** @return HasMany<FormSubmission, $this> */
    public function submissions(): HasMany
    {
        return $this->hasMany(FormSubmission::class);
    }

    /** Struktur terkunci setelah ada submission; perubahan = versi baru (BR-F8-1). */
    public function isLocked(): bool
    {
        return $this->submissions()->exists();
    }
}
