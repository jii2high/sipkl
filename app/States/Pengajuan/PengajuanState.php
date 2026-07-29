<?php

namespace App\States\Pengajuan;

use App\Models\PengajuanPkl;
use Spatie\ModelStates\State;
use Spatie\ModelStates\StateConfig;

/** @extends State<PengajuanPkl> */
abstract class PengajuanState extends State
{
    abstract public function label(): string;

    public static function config(): StateConfig
    {
        return parent::config()
            ->default(Draft::class)
            ->allowTransition(Draft::class, DiajukanKaprog::class)
            ->allowTransition(DiajukanKaprog::class, DitolakKaprog::class)
            ->allowTransition(DiajukanKaprog::class, SiapTerbit::class)
            ->allowTransition(DitolakKaprog::class, Draft::class)
            ->allowTransition(DitolakKaprog::class, DiajukanKaprog::class);
    }
}
