<?php

namespace App\Models;

use Barryvdh\DomPDF\Facade\Pdf;
use Database\Factories\SuratTugasMonitoringFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * @property-read PeriodePkl $periode
 * @property-read KompetensiKeahlian|null $kompetensiKeahlian
 * @property-read User|null $requestedBy
 * @property-read User $diterbitkanBy
 */
class SuratTugasMonitoring extends Model
{
    /** @use HasFactory<SuratTugasMonitoringFactory> */
    use HasFactory;

    protected $table = 'surat_tugas_monitoring';

    protected $fillable = [
        'periode_id',
        'lingkup',
        'kompetensi_keahlian_id',
        'requested_by',
        'diterbitkan_by',
        'nomor_surat',
        'tanggal_terbit',
        'file',
        'status',
        'berlaku_hingga',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_terbit' => 'date:Y-m-d',
            'berlaku_hingga' => 'date:Y-m-d',
        ];
    }

    /** @return BelongsTo<PeriodePkl, $this> */
    public function periode(): BelongsTo
    {
        return $this->belongsTo(PeriodePkl::class, 'periode_id');
    }

    /** @return BelongsTo<KompetensiKeahlian, $this> */
    public function kompetensiKeahlian(): BelongsTo
    {
        return $this->belongsTo(KompetensiKeahlian::class, 'kompetensi_keahlian_id');
    }

    /** @return BelongsTo<User, $this> */
    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /** @return BelongsTo<User, $this> */
    public function diterbitkanBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diterbitkan_by');
    }

    /**
     * @param  Builder<SuratTugasMonitoring>  $query
     * @return Builder<SuratTugasMonitoring>
     */
    public function scopeAktif(Builder $query): Builder
    {
        return $query->where('status', 'aktif');
    }

    /**
     * Penugasan aktif yang tercakup surat tugas ini (pembimbing yang di-cover).
     *
     * @return Collection<int, PenugasanPembimbing>
     */
    public function penugasansTercakup(): Collection
    {
        return PenugasanPembimbing::query()
            ->where('status', 'aktif')
            ->whereHas('pengajuan', fn (Builder $q) => $q->where('periode_id', $this->periode_id))
            ->when($this->lingkup === 'scoped', fn (Builder $q) => $q->whereHas(
                'pengajuan.siswa',
                fn (Builder $q2) => $q2->where('kompetensi_keahlian_id', $this->kompetensi_keahlian_id)
            ))
            ->with(['guruPembimbing', 'pengajuan.siswa.user', 'pengajuan.siswa.kompetensiKeahlian'])
            ->get();
    }

    /**
     * Render Blade → PDF, simpan ke storage, isi kolom `file`. Pola sama dengan surat pengantar (M4).
     */
    public function generatePdf(): static
    {
        $this->loadMissing(['periode', 'kompetensiKeahlian', 'diterbitkanBy', 'requestedBy']);
        $this->setRelation('penugasans', $this->penugasansTercakup());

        $pdf = Pdf::loadView('pdf.surat-tugas', ['suratTugas' => $this])
            ->setPaper('a4', 'portrait');

        $namaFile = "surat-tugas/{$this->id}-{$this->nomor_surat}.pdf";
        $namaFile = preg_replace('/[\/\\\:*?"<>|]/', '_', $namaFile);
        Storage::put($namaFile, $pdf->output());

        $this->update(['file' => $namaFile]);

        return $this;
    }
}
