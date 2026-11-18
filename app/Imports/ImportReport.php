<?php

namespace App\Imports;

/**
 * Rekap hasil satu kali impor massal (TASKS M2.9).
 *
 * Impor bersifat parsial: baris valid tetap masuk, baris bermasalah dilaporkan
 * dengan nomor baris spreadsheet agar Hubin bisa memperbaikinya.
 */
final class ImportReport
{
    private int $total = 0;

    /** @var list<array{baris: int, label: string}> */
    private array $berhasil = [];

    /** @var list<array{baris: int, pesan: string}> */
    private array $gagal = [];

    /** @var list<array{baris: int, label: string, saran: string}> */
    private array $tinjau = [];

    public function tambahBaris(): void
    {
        $this->total++;
    }

    public function sukses(int $baris, string $label): void
    {
        $this->berhasil[] = ['baris' => $baris, 'label' => $label];
    }

    public function gagal(int $baris, string $pesan): void
    {
        $this->gagal[] = ['baris' => $baris, 'pesan' => $pesan];
    }

    /**
     * Baris tersimpan tetapi mirip data yang sudah ada (soft-warning M2.3).
     */
    public function perluTinjau(int $baris, string $label, string $saran): void
    {
        $this->tinjau[] = ['baris' => $baris, 'label' => $label, 'saran' => $saran];
    }

    public function total(): int
    {
        return $this->total;
    }

    public function jumlahBerhasil(): int
    {
        return count($this->berhasil);
    }

    public function jumlahGagal(): int
    {
        return count($this->gagal);
    }

    public function jumlahPerluTinjau(): int
    {
        return count($this->tinjau);
    }

    /** @return list<array{baris: int, label: string}> */
    public function daftarBerhasil(): array
    {
        return $this->berhasil;
    }

    /** @return list<array{baris: int, pesan: string}> */
    public function daftarGagal(): array
    {
        return $this->gagal;
    }

    /** @return list<array{baris: int, label: string, saran: string}> */
    public function daftarPerluTinjau(): array
    {
        return $this->tinjau;
    }

    public function ringkasan(): string
    {
        $ringkasan = $this->jumlahBerhasil().' berhasil, '.$this->jumlahGagal().' gagal';

        if ($this->jumlahPerluTinjau() > 0) {
            $ringkasan .= ', '.$this->jumlahPerluTinjau().' perlu tinjau';
        }

        return $ringkasan.' dari '.$this->total.' baris.';
    }

    /**
     * Bentuk yang aman disimpan sebagai state Livewire.
     *
     * @return array{total: int, berhasil: list<array{baris: int, label: string}>, gagal: list<array{baris: int, pesan: string}>, perlu_tinjau: list<array{baris: int, label: string, saran: string}>, ringkasan: string}
     */
    public function toArray(): array
    {
        return [
            'total' => $this->total,
            'berhasil' => $this->berhasil,
            'gagal' => $this->gagal,
            'perlu_tinjau' => $this->tinjau,
            'ringkasan' => $this->ringkasan(),
        ];
    }
}
