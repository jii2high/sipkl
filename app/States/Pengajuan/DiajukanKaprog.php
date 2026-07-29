<?php

namespace App\States\Pengajuan;

class DiajukanKaprog extends PengajuanState
{
    public function label(): string
    {
        return 'Menunggu Verifikasi Kaprog';
    }
}
