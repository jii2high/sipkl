<?php

namespace App\Imports;

use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Throwable;

/**
 * Kerangka impor per baris (TASKS M2.9).
 *
 * Setiap baris dijalankan dalam transaksi sendiri supaya baris bermasalah tidak
 * membatalkan baris valid lainnya. Semua kegagalan dicatat ke `ImportReport`
 * dengan nomor baris spreadsheet (baris 1 = judul kolom).
 */
abstract class BaseImport implements SkipsEmptyRows, ToCollection, WithHeadingRow
{
    public function __construct(
        protected readonly ImportReport $report,
        protected readonly ?User $oleh = null,
    ) {}

    public function collection(Collection $rows): void
    {
        foreach ($rows as $index => $row) {
            /** @var array<string, mixed> $data */
            $data = $row->toArray();

            if ($this->barisKosong($data)) {
                continue;
            }

            $nomor = $index + 2;
            $this->report->tambahBaris();

            try {
                DB::transaction(fn () => $this->prosesBaris($data, $nomor));
            } catch (ValidationException $exception) {
                $this->report->gagal($nomor, $this->pesanValidasi($exception));
            } catch (QueryException $exception) {
                report($exception);
                $this->report->gagal($nomor, 'Baris bertabrakan dengan data yang sudah ada (kemungkinan duplikat).');
            } catch (Throwable $exception) {
                report($exception);
                $this->report->gagal($nomor, 'Baris gagal diproses karena kesalahan tak terduga.');
            }
        }
    }

    /**
     * Validasi + simpan satu baris. Lempar `ValidationException` bila tidak valid.
     *
     * @param  array<string, mixed>  $row
     */
    abstract protected function prosesBaris(array $row, int $nomor): void;

    /**
     * Ambil nilai pertama yang terisi dari beberapa kemungkinan judul kolom.
     *
     * @param  array<string, mixed>  $row
     */
    protected function ambil(array $row, string ...$kandidat): ?string
    {
        foreach ($kandidat as $kunci) {
            if (! array_key_exists($kunci, $row)) {
                continue;
            }

            $nilai = $row[$kunci];

            if ($nilai === null) {
                continue;
            }

            if (is_bool($nilai)) {
                return $nilai ? '1' : '0';
            }

            $nilai = trim((string) $nilai);

            if ($nilai !== '') {
                return $nilai;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    protected function barisKosong(array $row): bool
    {
        foreach ($row as $nilai) {
            if ($nilai !== null && trim((string) $nilai) !== '') {
                return false;
            }
        }

        return true;
    }

    /**
     * Boolean longgar untuk kolom "Aktif": 1/0, ya/tidak, true/false.
     */
    protected function boolean(?string $nilai, bool $default = true): bool
    {
        if ($nilai === null) {
            return $default;
        }

        return in_array(mb_strtolower(trim($nilai)), ['1', 'ya', 'y', 'true', 'aktif', 'yes'], true);
    }

    private function pesanValidasi(ValidationException $exception): string
    {
        $pesan = collect($exception->errors())->flatten()->filter()->implode(' ');

        return $pesan === '' ? 'Baris tidak valid.' : $pesan;
    }
}
