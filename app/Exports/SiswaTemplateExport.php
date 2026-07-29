<?php

namespace App\Exports;

use Illuminate\Support\Collection as SupportCollection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class SiswaTemplateExport implements FromCollection, WithHeadings
{
    /** @return SupportCollection<int, array<int, string>> */
    public function collection(): SupportCollection
    {
        return new SupportCollection;
    }

    /** @return list<string> */
    public function headings(): array
    {
        return ['nama', 'nis', 'email', 'kelas', 'kompetensi_keahlian', 'alamat', 'no_telp'];
    }
}
