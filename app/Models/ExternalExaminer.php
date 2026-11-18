<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

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
}
