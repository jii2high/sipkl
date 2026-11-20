<?php

namespace App\Actions\Sidang;

use App\Actions\Action;
use App\Jobs\SyncSidangResultsSummaryJob;
use App\Models\ExternalSession;
use App\Models\FormSubmission;
use App\Models\JadwalSidang;
use App\Services\ScoreService;
use Illuminate\Auth\Access\AuthorizationException;
use RuntimeException;

/**
 * Penguji menyimpan nilai lalu mengunci submission (F9 alur 3). Update berikutnya
 * ditolak 403 (KP-F9-1) kecuali ada window revisi yang disetujui Kaprog; saat window
 * itu terbuka, jawaban diganti dan revisi ditutup.
 */
final class SubmitScore extends Action
{
    public function handle(mixed ...$arguments): FormSubmission
    {
        [$submission, $input, $session] = $arguments;

        if (! $submission instanceof FormSubmission || ! $session instanceof ExternalSession || ! is_array($input)) {
            throw new RuntimeException('Submission, jawaban, dan sesi penguji wajib diisi.');
        }

        $saved = $this->transaction(function () use ($submission, $input, $session): FormSubmission {
            $locked = FormSubmission::query()->whereKey($submission->getKey())->lockForUpdate()->firstOrFail();

            if (! $locked->isTargetedBy($session)) {
                throw new AuthorizationException(__('pkl.sidang.not_original_examiner'));
            }

            $revision = null;

            if ($locked->is_submitted) {
                $revision = $locked->openRevision();

                if ($revision === null) {
                    throw new AuthorizationException(__('pkl.sidang.score_locked'));
                }
            }

            $jawaban = app(ScoreService::class)->normalize($locked->definition, $input);

            $locked->update([
                'jawaban' => $jawaban,
                'is_submitted' => true,
                'submitted_at' => now(),
                'revision_count' => $revision === null ? $locked->revision_count : $locked->revision_count + 1,
            ]);

            if ($revision !== null) {
                $revision->update(['jawaban_baru' => $jawaban, 'status' => 'selesai']);
            }

            JadwalSidang::query()
                ->whereKey($locked->jadwal_sidang_id)
                ->update(['status' => JadwalSidang::STATUS_SELESAI]);

            $this->audit(
                $locked,
                $revision === null ? 'nilai_sidang_dikunci' : 'nilai_sidang_direvisi',
                null,
                'terkunci',
                [
                    'nilai_total' => $jawaban['nilai_total'] ?? null,
                    'jadwal_sidang_id' => $locked->jadwal_sidang_id,
                ],
                $revision === null ? 'Nilai sidang disimpan dan dikunci' : 'Nilai sidang direvisi',
            );

            return $locked->refresh();
        });

        SyncSidangResultsSummaryJob::dispatch((int) $saved->getKey());

        return $saved;
    }
}
