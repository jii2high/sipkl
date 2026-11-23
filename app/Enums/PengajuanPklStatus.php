<?php

namespace App\Enums;

use App\Support\StatusChip;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

/**
 * State machine `pengajuan_pkl.status` (company group, TRD.md A5.3). Urutannya
 * `terbentuk → surat_terbit → berjalan → bubar`; transisi ditegakkan Action saat
 * tugas yang membutuhkannya dibuat (T4.2).
 */
enum PengajuanPklStatus: string implements HasColor, HasIcon, HasLabel
{
    case Terbentuk = 'terbentuk';
    case SuratTerbit = 'surat_terbit';
    case Berjalan = 'berjalan';
    case Bubar = 'bubar';

    public function label(): string
    {
        return StatusChip::label($this->value);
    }

    public function getLabel(): string
    {
        return $this->label();
    }

    public function getColor(): string
    {
        return StatusChip::color($this->value);
    }

    public function getIcon(): string
    {
        return StatusChip::icon($this->value);
    }
}
