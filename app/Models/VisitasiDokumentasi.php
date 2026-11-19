<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table(name: 'visitasi_dokumentasi')]
#[Fillable(['surat_tugas_visitasi_id', 'company_id', 'uploaded_by', 'tanggal_kunjungan', 'file_path', 'keterangan'])]
class VisitasiDokumentasi extends Model
{
    public const UPDATED_AT = null;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['tanggal_kunjungan' => 'date'];
    }

    /** @return BelongsTo<SuratTugasVisitasi, $this> */
    public function visitasi(): BelongsTo
    {
        return $this->belongsTo(SuratTugasVisitasi::class, 'surat_tugas_visitasi_id');
    }

    /** @return BelongsTo<Company, $this> */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /** @return BelongsTo<User, $this> */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
