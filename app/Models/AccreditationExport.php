<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'requested_by',
    'academic_period_id',
    'retry_of_id',
    'status',
    'snapshot_file_path',
    'snapshot_checksum',
    'row_count',
    'failure_reason',
    'requested_at',
    'started_at',
    'completed_at',
    'expires_at',
])]
class AccreditationExport extends Model
{
    public $timestamps = false;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'row_count' => 'integer',
            'requested_at' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }
}
