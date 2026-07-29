<?php

namespace App\States\Pengajuan;

class Draft extends PengajuanState
{
    public static string $name = 'draft';

    public function label(): string
    {
        return 'Draft';
    }
}
