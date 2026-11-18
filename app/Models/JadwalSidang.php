<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

#[Table(name: 'jadwal_sidang')]
#[Fillable(['pengajuan_siswa_id', 'external_examiner_id', 'tanggal', 'urutan', 'status', 'scheduled_by'])]
class JadwalSidang extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'urutan' => 'integer',
        ];
    }
}
