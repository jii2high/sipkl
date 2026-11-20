<?php

namespace App\Actions\Visitasi;

use App\Actions\Action;
use App\Exceptions\DocumentIssuanceHeld;
use App\Models\SuratTugasVisitasi;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use RuntimeException;

/**
 * Hubin menyetujui pengajuan visitasi (F5). Keputusan terpisah dari penerbitan
 * surat: status berpindah ke `disetujui` walau TTD Kepsek belum ada, lalu
 * penerbitan dicoba terpisah. Surat yang tertahan tetap `disetujui` tanpa nomor
 * dan Hubin diberi tahu; tombol "Terbitkan surat" menerbitkannya kemudian.
 */
final class ApproveVisitasi extends Action
{
    public function __construct(private readonly IssueSuratTugasVisitasi $letters) {}

    public function handle(mixed ...$arguments): int
    {
        [$visitasi, $hubin] = $arguments;

        if (! $visitasi instanceof SuratTugasVisitasi || ! $hubin instanceof User) {
            throw new RuntimeException('Visitasi dan Hubin wajib diisi.');
        }

        Gate::forUser($hubin)->authorize('approve', $visitasi);

        $approved = $this->transaction(function () use ($visitasi, $hubin): int {
            $locked = SuratTugasVisitasi::query()->whereKey($visitasi->getKey())->lockForUpdate()->firstOrFail();

            if ($locked->status !== 'diajukan') {
                return 0;
            }

            $this->transitionStatus($locked, 'disetujui', 'diajukan', [
                'approved_by' => $hubin->getKey(),
                'approved_at' => now(),
            ]);
            $this->audit($locked, 'visitasi_disetujui', 'diajukan', 'disetujui', [], 'Pengajuan visitasi disetujui');

            return 1;
        });

        if ($approved !== 1) {
            return $approved;
        }

        $visitasi->refresh();

        try {
            $this->letters->handle($visitasi, $hubin);
        } catch (DocumentIssuanceHeld) {
            // Surat ditahan sampai TTD Kepsek diunggah; DocumentService sudah memberi tahu Hubin.
        }

        return 1;
    }
}
