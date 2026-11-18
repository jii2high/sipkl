<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

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
}
