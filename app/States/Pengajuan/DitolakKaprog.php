<?php

namespace App\States\Pengajuan;

class DitolakKaprog extends PengajuanState
{
    public static string $name = 'ditolak';

    public function label(): string
    {
        return 'Ditolak oleh Kaprog';
    }
}
