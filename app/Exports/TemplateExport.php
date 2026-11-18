<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

/**
 * Template xlsx untuk impor massal (TASKS M2.9).
 *
 * Judul kolom harus sama dengan yang dibaca `CompaniesImport`/`UsersImport`/
 * `PeriodsImport` (Laravel Excel men-slug judul menjadi kunci baris).
 */
final class TemplateExport implements FromArray, ShouldAutoSize, WithHeadings, WithTitle
{
    /**
     * @param  list<string>  $headings
     * @param  list<list<mixed>>  $contoh
     */
    public function __construct(
        private readonly array $headings,
        private readonly array $contoh,
        private readonly string $judul,
    ) {}

    /**
     * @return list<list<mixed>>
     */
    public function array(): array
    {
        return $this->contoh;
    }

    /**
     * @return list<string>
     */
    public function headings(): array
    {
        return $this->headings;
    }

    public function title(): string
    {
        return $this->judul;
    }
}
