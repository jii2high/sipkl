<?php

namespace App\Models;

use Database\Factories\SuratBalasanFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SuratBalasan extends Model
{
    /** @use HasFactory<SuratBalasanFactory> */
    use HasFactory;

    protected $table = 'surat_balasan';

    protected $fillable = [
        'pengajuan_id',
        'file_path',
    ];

    /** @return BelongsTo<PengajuanPkl, $this> */
    public function pengajuan(): BelongsTo
    {
        return $this->belongsTo(PengajuanPkl::class, 'pengajuan_id');
    }
}
