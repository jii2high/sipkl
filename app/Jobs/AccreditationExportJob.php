<?php

namespace App\Jobs;

use App\Models\AccreditationExport;
use App\Models\SidangResultsSummary;
use App\Models\User;
use App\Notifications\Notification;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/**
 * Ekspor akreditasi chunk 500 (F13, TRD.md A10). Snapshot CSV ditulis ke disk
 * privat; baris yang tidak lengkap menggagalkan seluruh attempt sehingga tidak
 * ada file parsial yang bisa diunduh (KP-F13-1, BR-F13-2).
 */
class AccreditationExportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 1;

    /** @var array<int, string> */
    private const HEADERS = [
        'Nama Siswa',
        'NIS',
        'Jurusan',
        'Perusahaan',
        'Nilai per Kategori',
        'Status Kelulusan',
        'Penguji',
        'Tanggal Sidang',
    ];

    public function __construct(public readonly int $exportId) {}

    public function handle(): void
    {
        $export = AccreditationExport::query()->find($this->exportId);

        if ($export === null || $export->status === 'completed') {
            return;
        }

        $export->update(['status' => 'running', 'started_at' => now()]);

        $path = 'exports/'.$export->getKey().'-'.Str::uuid().'.csv';
        $absolute = tempnam(sys_get_temp_dir(), 'akreditasi-');

        if ($absolute === false) {
            throw new RuntimeException('Berkas sementara ekspor tidak dapat dibuat.');
        }

        $handle = fopen($absolute, 'w');

        if ($handle === false) {
            throw new RuntimeException('Berkas sementara ekspor tidak dapat dibuka.');
        }

        $rows = 0;

        try {
            fputcsv($handle, self::HEADERS);

            $this->query($export)->chunkById(
                (int) config('pkl.export.chunk_size'),
                function ($summaries) use ($handle, &$rows): void {
                    foreach ($summaries as $summary) {
                        fputcsv($handle, $this->row($summary));
                        $rows++;
                    }
                },
            );
        } catch (Throwable $exception) {
            fclose($handle);
            @unlink($absolute);

            $export->update([
                'status' => 'failed',
                'failure_reason' => Str::limit($exception->getMessage(), 1000),
            ]);

            throw $exception;
        }

        fclose($handle);

        $disk = Storage::disk('local');
        $disk->put($path, fopen($absolute, 'r'));
        @unlink($absolute);

        $export->update([
            'status' => 'completed',
            'snapshot_file_path' => $path,
            'snapshot_checksum' => hash_file('sha256', $disk->path($path)),
            'row_count' => $rows,
            'completed_at' => now(),
            'expires_at' => now()->addDays((int) config('pkl.export.retention_days')),
        ]);

        $this->notify($export, $rows);
    }

    /** @return Builder<SidangResultsSummary> */
    private function query(AccreditationExport $export)
    {
        return SidangResultsSummary::query()
            ->when(
                $export->academic_period_id !== null,
                fn ($query) => $query->where('academic_period_id', $export->academic_period_id),
            )
            ->orderBy('id');
    }

    /**
     * @return array<int, string>
     */
    private function row(SidangResultsSummary $summary): array
    {
        $nilai = $summary->nilai_total_per_kategori;

        if (! is_array($nilai) || $nilai === []) {
            throw new RuntimeException('Baris '.$summary->getKey().' tidak punya nilai per kategori.');
        }

        $row = [
            trim((string) $summary->nama_siswa),
            trim((string) $summary->nis),
            trim((string) $summary->jurusan),
            trim((string) $summary->nama_perusahaan),
            (string) json_encode($nilai, JSON_UNESCAPED_UNICODE),
            trim((string) ($summary->status_kelulusan ?? '')),
            trim((string) $summary->nama_penguji),
            $summary->tanggal_sidang?->toDateString() ?? '',
        ];

        foreach ([0, 1, 2, 3, 6, 7] as $index) {
            if ($row[$index] === '') {
                throw new RuntimeException('Baris '.$summary->getKey().' tidak lengkap untuk diekspor.');
            }
        }

        return $row;
    }

    private function notify(AccreditationExport $export, int $rows): void
    {
        $user = User::query()->find($export->requested_by);

        if ($user === null) {
            return;
        }

        app(NotificationService::class)->send($user, new Notification(
            'Ekspor akreditasi selesai',
            'Ekspor akreditasi '.$rows.' baris siap diunduh. Tautan berlaku '.
                config('pkl.export.retention_days').' hari.',
            route('export.download', $export),
        ), ['related' => $export]);
    }
}
