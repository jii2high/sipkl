<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

#[Table(name: 'sidang_results_summary', timestamps: false)]
#[Fillable([
    'pengajuan_siswa_id',
    'form_submission_id',
    'nama_siswa',
    'nis',
    'jurusan',
    'nama_perusahaan',
    'academic_period_id',
    'nilai_total_per_kategori',
    'status_kelulusan',
    'nama_penguji',
    'tanggal_sidang',
    'synced_at',
])]
class SidangResultsSummary extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'nilai_total_per_kategori' => 'array',
            'tanggal_sidang' => 'date',
            'synced_at' => 'datetime',
        ];
    }
}
