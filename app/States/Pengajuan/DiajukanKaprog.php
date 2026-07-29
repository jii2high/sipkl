<?php

namespace App\States\Pengajuan;

class DiajukanKaprog extends PengajuanState
{
    public static string $name = 'diajukan';

    public function label(): string
    {
        return 'Diajukan ke Kaprog';
    }
}
