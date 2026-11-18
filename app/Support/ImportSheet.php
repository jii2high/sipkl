<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Pembaca berkas impor massal (T2.1/T2.2). Baris pertama dianggap judul kolom;
 * sisanya dikembalikan sebagai array asosiatif siap dipakai `ImportData`. Baris
 * kosong dilewati supaya tidak dihitung sebagai kegagalan.
 */
final class ImportSheet
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public static function rows(UploadedFile $file): array
    {
        $sheet = Excel::toCollection(null, $file)->first();

        if ($sheet === null) {
            return [];
        }

        $header = $sheet->shift();

        if ($header === null) {
            return [];
        }

        $keys = array_map(
            static fn (mixed $value): string => strtolower(trim((string) $value)),
            array_values($header->toArray()),
        );

        if ($keys === []) {
            return [];
        }

        $rows = [];

        foreach ($sheet as $line) {
            $values = array_pad(array_slice(array_values($line->toArray()), 0, count($keys)), count($keys), null);
            $row = array_combine($keys, $values);

            if (array_filter($row, static fn (mixed $value): bool => filled($value)) === []) {
                continue;
            }

            $rows[] = $row;
        }

        return $rows;
    }
}
