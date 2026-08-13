<?php

namespace App\Models;

use Database\Factories\FormulirPenilaianFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FormulirPenilaian extends Model
{
    /** @use HasFactory<FormulirPenilaianFactory> */
    use HasFactory;

    protected $table = 'formulir_penilaian';

    protected $fillable = [
        'penugasan_id',
        'siswa_id',
        'periode_pkl_id',
        'guru_pembimbing_id',
        'sakit',
        'izin',
        'alpa',
        'catatan_guru_pembimbing',
        'nama_pembimbing_industri',
        'nilai_akhir',
        'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<PenugasanPembimbing, $this> */
    public function penugasan(): BelongsTo
    {
        return $this->belongsTo(PenugasanPembimbing::class, 'penugasan_id');
    }

    /** @return BelongsTo<User, $this> */
    public function siswa(): BelongsTo
    {
        return $this->belongsTo(User::class, 'siswa_id');
    }

    /** @return BelongsTo<PeriodePkl, $this> */
    public function periodePkl(): BelongsTo
    {
        return $this->belongsTo(PeriodePkl::class, 'periode_pkl_id');
    }

    /** @return BelongsTo<User, $this> */
    public function guruPembimbing(): BelongsTo
    {
        return $this->belongsTo(User::class, 'guru_pembimbing_id');
    }

    /** @return HasMany<NilaiPenilaian, $this> */
    public function nilaiPenilaians(): HasMany
    {
        return $this->hasMany(NilaiPenilaian::class, 'formulir_penilaian_id');
    }

    /**
     * BR28: Nilai Akhir dihitung 2 tahap — rata-rata leaf terisi per kelompok
     * (umum/jurusan — derive dari kompetensi_keahlian_id), lalu rata-rata antar kelompok.
     * Sumber kebenaran sebelum submit; kolom nilai_akhir = snapshot saat submit.
     */
    public function getNilaiAkhirAttribute(): ?float
    {
        $rataPerKategori = [];

        foreach ($this->nilaiPenilaians()->with([
            'indikatorPenilaian',
            'sectionPenilaian.indikatorPenilaian',
            'subSectionPenilaian.sectionPenilaian.indikatorPenilaian',
        ])->get() as $nilai) {
            $kkId = $nilai->subSectionPenilaian?->sectionPenilaian->indikatorPenilaian->kompetensi_keahlian_id
                ?? $nilai->sectionPenilaian?->indikatorPenilaian->kompetensi_keahlian_id
                ?? $nilai->indikatorPenilaian?->kompetensi_keahlian_id;

            $kategori = $kkId === null ? 'umum' : 'jurusan';
            $rataPerKategori[$kategori][] = (float) $nilai->nilai;
        }

        if ($rataPerKategori === []) {
            return null;
        }

        $rataKategori = array_map(
            fn (array $grup): float => array_sum($grup) / count($grup),
            $rataPerKategori
        );

        return round(array_sum($rataKategori) / count($rataKategori), 1);
    }
}
