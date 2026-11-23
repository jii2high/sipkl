<?php

namespace App\Enums;

use App\Enums\Concerns\HasTransitions;
use App\Enums\Contracts\StateEnum;
use App\Enums\Transitions\TransitionTable;
use App\Support\StatusChip;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

/**
 * State machine `pengajuan_siswa.status` (TRD.md §5.2). Satu Enum, satu tabel
 * transisi, satu Action per transisi. Aktor ditulis sebagai nilai `user_roles.role`.
 */
enum PengajuanStatus: string implements HasColor, HasIcon, HasLabel, StateEnum
{
    use HasTransitions;

    case Draft = 'draft';
    case MenungguVerifikasiKaprog = 'menunggu_verifikasi_kaprog';
    case DitolakKaprog = 'ditolak_kaprog';
    case MenungguTtdWalas = 'menunggu_ttd_walas';
    case DitolakWalas = 'ditolak_walas';
    case MenungguSuratPengantar = 'menunggu_surat_pengantar';
    case SuratPengantarTerbit = 'surat_pengantar_terbit';
    case MenungguBalasanPerusahaan = 'menunggu_balasan_perusahaan';
    case Rejected = 'rejected';
    case SedangPkl = 'sedang_pkl';
    case Withdrawn = 'withdrawn';

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

    public static function transitions(): TransitionTable
    {
        return new TransitionTable([
            self::Draft->value => [
                self::MenungguVerifikasiKaprog->value => [Role::Siswa->value],
            ],
            self::MenungguVerifikasiKaprog->value => [
                self::MenungguTtdWalas->value => [Role::Kaprog->value],
                self::DitolakKaprog->value => [Role::Kaprog->value],
            ],
            self::DitolakKaprog->value => [
                self::Draft->value => [Role::Siswa->value],
            ],
            self::MenungguTtdWalas->value => [
                self::MenungguSuratPengantar->value => [Role::Walas->value],
                self::DitolakWalas->value => [Role::Walas->value],
            ],
            self::DitolakWalas->value => [
                self::Draft->value => [Role::Siswa->value],
            ],
            self::MenungguSuratPengantar->value => [
                self::SuratPengantarTerbit->value => [Role::Hubin->value],
            ],
            self::SuratPengantarTerbit->value => [
                self::MenungguBalasanPerusahaan->value => [Role::Siswa->value],
            ],
            self::MenungguBalasanPerusahaan->value => [
                self::SedangPkl->value => [Role::Kaprog->value],
                self::Rejected->value => [Role::Kaprog->value],
            ],
            self::SedangPkl->value => [
                self::Withdrawn->value => [Role::Kaprog->value],
            ],
        ]);
    }
}
