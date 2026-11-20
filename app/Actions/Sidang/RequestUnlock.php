<?php

namespace App\Actions\Sidang;

use App\Actions\Action;
use App\Models\ExternalSession;
use App\Models\FormSubmission;
use App\Models\NilaiSidangRevision;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * Penguji mengajukan revisi nilai dari portal (Q-I/F9 alur 5). Baris dibuat dengan
 * status `menunggu_persetujuan`; tautan unlock baru terbit setelah Kaprog menyetujui.
 */
final class RequestUnlock extends Action
{
    public function handle(mixed ...$arguments): NilaiSidangRevision
    {
        [$submission, $session, $reason] = $arguments;

        if (! $submission instanceof FormSubmission || ! $session instanceof ExternalSession) {
            throw new RuntimeException('Submission dan sesi penguji wajib diisi.');
        }

        $reason = trim((string) $reason);

        if (mb_strlen($reason) < (int) config('pkl.rejection_note.min_length', 10)) {
            throw ValidationException::withMessages(['reason' => __('pkl.sidang.reason_min')]);
        }

        return $this->transaction(function () use ($submission, $session, $reason): NilaiSidangRevision {
            $locked = FormSubmission::query()->whereKey($submission->getKey())->lockForUpdate()->firstOrFail();

            if (! $locked->isTargetedBy($session)) {
                throw new AuthorizationException(__('pkl.sidang.not_original_examiner'));
            }

            if (! $locked->is_submitted) {
                throw ValidationException::withMessages(['reason' => __('pkl.sidang.not_submitted')]);
            }

            if ($locked->pendingRevision() !== null || $locked->openRevision() !== null) {
                throw ValidationException::withMessages(['reason' => __('pkl.sidang.revision_pending')]);
            }

            $revision = NilaiSidangRevision::query()->create([
                'form_submission_id' => $locked->getKey(),
                'jawaban_lama' => $locked->jawaban,
                'reported_by' => 'penguji',
                'reason' => $reason,
                'status' => 'menunggu_persetujuan',
            ]);

            $this->audit(
                $revision,
                'revisi_nilai_diajukan',
                null,
                'menunggu_persetujuan',
                ['form_submission_id' => $locked->getKey(), 'alasan' => $reason],
                'Revisi nilai diajukan penguji',
            );

            return $revision;
        });
    }
}
