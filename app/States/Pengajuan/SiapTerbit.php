<?php

namespace App\States\Pengajuan;

class SiapTerbit extends PengajuanState
{
    public static string $name = 'disetujui';

    public function label(): string
    {
        return 'Disetujui / Siap Terbit';
    }
}
