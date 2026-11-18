<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['pengajuan_siswa_id', 'version_number', 'source_bimbingan_submission_id', 'file_path', 'created_by'])]
class LaporanVersion extends Model
{
    public const UPDATED_AT = null;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['version_number' => 'integer'];
    }
}
