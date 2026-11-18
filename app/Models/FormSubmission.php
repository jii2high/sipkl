<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'form_definition_id',
    'form_type',
    'jadwal_sidang_id',
    'pengajuan_siswa_id',
    'company_id',
    'academic_period_id',
    'jawaban',
    'is_submitted',
    'submitted_at',
    'revision_count',
])]
class FormSubmission extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'jawaban' => 'array',
            'nilai_total' => 'decimal:2',
            'is_submitted' => 'boolean',
            'submitted_at' => 'datetime',
            'revision_count' => 'integer',
        ];
    }
}
