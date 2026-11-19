<?php

namespace App\Enums;

use App\Support\StatusChip;

/**
 * State machine `pengajuan_pkl.status` (company group, TRD.md §5.3). Urutannya
 * `terbentuk → surat_terbit → berjalan → bubar`; transisi ditegakkan Action saat
 * tugas yang membutuhkannya dibuat (T4.2).
 */
enum PengajuanPklStatus: string
{
    case Terbentuk = 'terbentuk';
    case SuratTerbit = 'surat_terbit';
    case Berjalan = 'berjalan';
    case Bubar = 'bubar';

    public function label(): string
    {
        return StatusChip::label($this->value);
    }
}
