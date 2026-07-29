<?php

namespace App\Exports;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class GuruTemplateExport implements FromCollection, WithHeadings
{
    public function collection(): SupportCollection
    {
        return new SupportCollection();
    }

    public function headings(): array
    {
        return ['nama', 'nip', 'email', 'jabatan', 'kompetensi_keahlian'];
    }
}
