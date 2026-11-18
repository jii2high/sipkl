<?php

namespace App\Jobs;

use App\Services\DocumentService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

/**
 * Buat PDF + QR sebuah surat (TRD.md §10, BR-F12-4). Idempoten: nomor dan path
 * diturunkan dari baris dokumen, sehingga retry menimpa berkas yang sama dan
 * tidak pernah menerbitkan nomor baru. Kegagalan masuk `failed_jobs` tanpa
 * rahasia apa pun.
 */
class GenerateDocumentPdfJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 3;

    public function __construct(
        public string $jenisSurat,
        public int $documentId,
    ) {
        $this->afterCommit = true;
    }

    /** @return array<int, int> */
    public function backoff(): array
    {
        return [10, 60, 300];
    }

    public function handle(DocumentService $documents): void
    {
        $documents->renderPdf($this->jenisSurat, $this->documentId);
    }
}
