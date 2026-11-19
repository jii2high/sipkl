<?php

namespace App\Actions\Plotting;

use App\Actions\Action;
use App\Exceptions\DocumentIssuanceHeld;
use App\Models\GuruPembimbingPlotting;
use App\Models\User;
use App\Services\DocumentService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use RuntimeException;

final class ReplaceGuru extends Action
{
    public function __construct(private readonly IssueSuratTugasPembimbing $letters) {}

    public function handle(mixed ...$arguments): GuruPembimbingPlotting
    {
        [$plotting, $guruBaru, $kaprog] = $arguments;

        if (! $plotting instanceof GuruPembimbingPlotting || ! $guruBaru instanceof User || ! $kaprog instanceof User) {
            throw new RuntimeException('Plotting, guru baru, dan Kaprog wajib diisi.');
        }

        Gate::forUser($kaprog)->authorize('replace', $plotting);

        if (! $guruBaru->is_active || ! $guruBaru->hasRole('guru_pembimbing')) {
            throw ValidationException::withMessages(['guruId' => 'Pilih guru pembimbing yang masih aktif.']);
        }

        return $this->transaction(function () use ($plotting, $guruBaru, $kaprog): GuruPembimbingPlotting {
            $locked = GuruPembimbingPlotting::query()->whereKey($plotting->getKey())->lockForUpdate()->firstOrFail();
            $guruLamaId = (int) $locked->guru_id;

            if ($guruLamaId === (int) $guruBaru->getKey()) {
                return $locked->load(['guru', 'company', 'academicPeriod', 'suratTugas']);
            }

            $oldLetter = $locked->suratTugas()->lockForUpdate()->first();

            if ($oldLetter !== null) {
                app(DocumentService::class)->revoke(
                    $oldLetter,
                    DocumentService::JENIS_TUGAS_PEMBIMBING,
                    $kaprog,
                    'Guru pembimbing diganti.',
                );
                $oldLetter->update(['superseded_at' => now()]);
            }

            $locked->update(['guru_id' => $guruBaru->getKey()]);

            $this->audit(
                $locked,
                'guru_pembimbing_diganti',
                (string) $guruLamaId,
                (string) $guruBaru->getKey(),
                [
                    'guru_lama_id' => $guruLamaId,
                    'guru_baru_id' => $guruBaru->getKey(),
                    'nomor_surat_lama' => $oldLetter?->nomor_surat,
                ],
                'Guru pembimbing diganti',
            );

            try {
                $this->letters->handle($locked, $kaprog);
            } catch (DocumentIssuanceHeld) {
                // Plotting tetap berlaku; DocumentService already notified Hubin.
            }

            return $locked->refresh()->load(['guru', 'company', 'academicPeriod', 'suratTugas']);
        });
    }
}
