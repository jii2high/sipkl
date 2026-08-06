<?php

namespace App\Models;

use Database\Factories\KunjunganMonitoringFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read PenugasanPembimbing $penugasan
 * @property-read SuratTugasMonitoring $suratTugas
 */
class KunjunganMonitoring extends Model
{
    /** @use HasFactory<KunjunganMonitoringFactory> */
    use HasFactory;

    protected $table = 'kunjungan_monitoring';

    protected $fillable = [
        'penugasan_id',
        'surat_tugas_id',
        'tanggal_kunjungan',
        'catatan',
        'dokumentasi_path',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_kunjungan' => 'date:Y-m-d',
        ];
    }

    /** @return BelongsTo<PenugasanPembimbing, $this> */
    public function penugasan(): BelongsTo
    {
        return $this->belongsTo(PenugasanPembimbing::class, 'penugasan_id');
    }

    /** @return BelongsTo<SuratTugasMonitoring, $this> */
    public function suratTugas(): BelongsTo
    {
        return $this->belongsTo(SuratTugasMonitoring::class, 'surat_tugas_id');
    }
}
