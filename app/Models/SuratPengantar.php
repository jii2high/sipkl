<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class SuratPengantar extends Model
{
    use HasFactory;

    protected $table = 'surat_pengantar';

    protected $fillable = [
        'nomor_surat',
        'perusahaan_id',
        'periode_id',
        'tanggal_terbit',
        'generated_by',
        'file_path',
    ];

    protected $casts = [
        'tanggal_terbit' => 'date',
    ];

    public function perusahaan(): BelongsTo
    {
        return $this->belongsTo(Perusahaan::class, 'perusahaan_id');
    }

    public function periode(): BelongsTo
    {
        return $this->belongsTo(PeriodePkl::class, 'periode_id');
    }

    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    public function pengajuans(): BelongsToMany
    {
        return $this->belongsToMany(PengajuanPkl::class, 'surat_pengantar_pengajuan', 'surat_pengantar_id', 'pengajuan_id');
    }
}
