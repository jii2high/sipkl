<?php

namespace App\Actions\Master;

use App\Actions\Action;
use App\Exceptions\CompanyMergeException;
use App\Models\Company;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Gabungkan dua perusahaan (BR-F2-4, KP-F2-3). Perusahaan sumber diberi
 * `merged_into_id` dan seluruh referensi (`company_id`) dipindah ke perusahaan
 * tujuan dalam satu transaksi, dengan jejak audit.
 *
 * Catatan penting: `pengajuan_siswa` punya FK komposit
 * `(pengajuan_pkl_id, company_id, academic_period_id)` ke `pengajuan_pkl`. Kedua
 * tabel harus berubah bersamaan, tetapi MySQL memeriksa FK tiap statement sehingga
 * urutan apa pun melanggar di tengah jalan. Karena setiap tabel referensi
 * diperbarui di transaksi yang sama dengan tujuan yang sama, pemeriksaan FK
 * dimatikan sementara lalu dinyalakan lagi; konsistensi tetap terjaga saat COMMIT.
 */
final class MergeCompany extends Action
{
    /** Tabel yang menyimpan `company_id` (docs/schema.sql). */
    private const REFERENCING_TABLES = [
        'pengajuan_pkl',
        'pengajuan_siswa',
        'guru_pembimbing_plotting',
        'surat_tugas_visitasi_companies',
        'visitasi_dokumentasi',
        'form_submissions',
    ];

    public function handle(mixed ...$arguments): Company
    {
        /** @var Company $source */
        [$source, $target, $actor] = $arguments + [null, null, null];

        $this->guard($source, $target, $actor);

        return $this->transaction(function () use ($source, $target, $actor): Company {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');

            try {
                foreach (self::REFERENCING_TABLES as $table) {
                    $this->moveReferences($table, $source, $target);
                }

                $source->newQuery()->whereKey($source->getKey())->update([
                    'merged_into_id' => $target->getKey(),
                ]);
            } finally {
                DB::statement('SET FOREIGN_KEY_CHECKS=1');
            }

            app(AuditService::class)->log(
                description: "Perusahaan \"{$source->nama}\" digabung ke \"{$target->nama}\"",
                subject: $source,
                event: 'merged',
                properties: [
                    'dari_id' => $source->getKey(),
                    'dari_nama' => $source->nama,
                    'ke_id' => $target->getKey(),
                    'ke_nama' => $target->nama,
                ],
                causer: $actor instanceof User ? $actor : null,
            );

            return $target;
        });
    }

    private function guard(Company $source, ?Company $target, mixed $actor): void
    {
        if (! $target instanceof Company) {
            throw CompanyMergeException::missingTarget();
        }

        if ($source->getKey() === $target->getKey()) {
            throw CompanyMergeException::selfMerge();
        }

        if ($source->merged_into_id !== null) {
            throw CompanyMergeException::alreadyMerged($source->nama);
        }

        if ($target->merged_into_id !== null) {
            throw CompanyMergeException::targetMerged($target->nama);
        }
    }

    private function moveReferences(string $table, Company $source, Company $target): void
    {
        try {
            DB::table($table)
                ->where('company_id', $source->getKey())
                ->update(['company_id' => $target->getKey()]);
        } catch (QueryException) {
            throw CompanyMergeException::conflictingReference(__('pkl.company.table.'.$table));
        }
    }
}
