<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SuratBalasan extends Model
{
    use HasFactory;

    protected $table = 'surat_balasan';

    protected $fillable = [
        'pengajuan_id',
        'file_path',
    ];

    public function pengajuan(): BelongsTo
    {
        return $this->belongsTo(PengajuanPkl::class, 'pengajuan_id');
    }
}
