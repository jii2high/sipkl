<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class TemplateGuruExport implements FromArray, WithHeadings
{
    /** @return array<int, string> */
    public function headings(): array
    {
        return ['nama', 'nip', 'email', 'jabatan', 'kompetensi_keahlian'];
    }

    /** @return array<int, array<int, string>> */
    public function array(): array
    {
        return [
            ['Contoh Guru', '198001012010011001', 'guru@test.com', 'kaprog', 'RPL'],
        ];
    }
}
