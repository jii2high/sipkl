<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class TemplateSiswaExport implements FromArray, WithHeadings
{
    /** @return array<int, string> */
    public function headings(): array
    {
        return ['nama', 'nis', 'email', 'periode', 'kompetensi_keahlian', 'alamat', 'no_telp'];
    }

    /** @return array<int, array<int, string>> */
    public function array(): array
    {
        return [
            ['Contoh Siswa', '1234567890', 'siswa@test.com', 'Ganjil 2026/2027', 'RPL', 'Jl. Contoh No. 1', '081234567890'],
        ];
    }
}
