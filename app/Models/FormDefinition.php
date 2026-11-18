<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

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
}
