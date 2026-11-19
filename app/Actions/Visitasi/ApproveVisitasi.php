<?php

namespace App\Actions\Visitasi;

use App\Actions\Action;
use App\Models\SuratTugasVisitasi;
use App\Models\User;
use App\Support\JakartaTime;
use Illuminate\Support\Facades\Gate;
use RuntimeException;

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

        return $this->transaction(function () use ($visitasi, $hubin): int {
            $locked = SuratTugasVisitasi::query()->whereKey($visitasi->getKey())->lockForUpdate()->firstOrFail();

            if ($locked->status !== 'diajukan') {
                return 0;
            }

            $this->letters->issueForApproval($locked, $hubin);
            $locked->refresh();

            $start = JakartaTime::today();
            $end = $start->addDays((int) config('pkl.visitasi.default_validity_days', 7) - 1);

            $this->transitionStatus($locked, 'disetujui', 'diajukan', [
                'approved_by' => $hubin->getKey(),
                'approved_at' => now(),
                'tanggal_mulai' => $start->toDateString(),
                'tanggal_selesai' => $end->toDateString(),
            ]);
            $this->audit($locked, 'visitasi_disetujui', 'diajukan', 'disetujui', [], 'Pengajuan visitasi disetujui');

            return 1;
        });
    }
}
