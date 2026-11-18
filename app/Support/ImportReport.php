<?php

namespace App\Support;

/**
 * Hasil satu kali impor massal (T2.1/T2.2, KP-F2-2). Impor berjalan transaksi per
 * baris: baris yang gagal tidak membatalkan baris lain, dan tiap kegagalan dicatat
 * dengan nomor baris serta pesannya.
 */
final class ImportReport
{
    /**
     * @param  array<int, array{row: int, messages: array<int, string>}>  $failed
     */
    public function __construct(
        public readonly string $kind,
        public readonly int $total,
        public readonly int $imported,
        public readonly array $failed = [],
    ) {}

    public function failedCount(): int
    {
        return count($this->failed);
    }

    public function hasFailures(): bool
    {
        return $this->failed !== [];
    }

    public function isClean(): bool
    {
        return $this->imported === $this->total;
    }

    /** Ringkasan Bahasa Indonesia untuk notifikasi dan laporan unduhan. */
    public function summary(): string
    {
        return __('pkl.import.summary', [
            'imported' => $this->imported,
            'total' => $this->total,
            'failed' => $this->failedCount(),
        ]);
    }

    /** Baris-baris bermasalah, satu baris teks per kegagalan. */
    public function failureLines(): array
    {
        $lines = [];

        foreach ($this->failed as $failure) {
            $lines[] = __('pkl.import.failure_line', [
                'row' => $failure['row'],
                'messages' => implode('; ', $failure['messages']),
            ]);
        }

        return $lines;
    }
}
