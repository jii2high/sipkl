<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table(name: 'surat_tugas_pembimbing')]
#[Fillable([
    'guru_pembimbing_plotting_id',
    'guru_id',
    'tahun_ajaran',
    'nomor_urut',
    'nomor_surat',
    'signature_asset_id',
    'signed_at',
    'file_path',
    'superseded_at',
])]
class SuratTugasPembimbing extends Model
{
    public const UPDATED_AT = null;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'nomor_urut' => 'integer',
            'signed_at' => 'datetime',
            'superseded_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<GuruPembimbingPlotting, $this> */
    public function plotting(): BelongsTo
    {
        return $this->belongsTo(GuruPembimbingPlotting::class, 'guru_pembimbing_plotting_id');
    }

    /** @return BelongsTo<User, $this> */
    public function guru(): BelongsTo
    {
        return $this->belongsTo(User::class, 'guru_id');
    }
}
