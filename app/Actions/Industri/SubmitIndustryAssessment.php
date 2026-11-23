<?php

namespace App\Actions\Industri;

use App\Actions\Action;
use App\Models\ExternalSession;
use App\Models\FormSubmission;
use App\Services\ExternalOtpService;
use App\Services\ScoreService;
use Illuminate\Auth\Access\AuthorizationException;
use RuntimeException;

/**
 * Pembimbing industri menyimpan nilai lalu mengunci submission (F10). Tidak ada
 * revisi: update berikutnya ditolak 403 (BR-F10-1, KP-F10-3).
 */
final class SubmitIndustryAssessment extends Action
{
    public function handle(mixed ...$arguments): FormSubmission
    {
        [$submission, $input, $session] = $arguments;

        if (! $submission instanceof FormSubmission || ! $session instanceof ExternalSession || ! is_array($input)) {
            throw new RuntimeException('Submission, jawaban, dan sesi industri wajib diisi.');
        }

        if (! app(ExternalOtpService::class)->isUsable($session)) {
            throw new AuthorizationException(__('pkl.industri.not_targeted'));
        }

        return $this->transaction(function () use ($submission, $input, $session): FormSubmission {
            $locked = FormSubmission::query()->whereKey($submission->getKey())->lockForUpdate()->firstOrFail();

            if (! $locked->isTargetedBy($session)) {
                throw new AuthorizationException(__('pkl.industri.not_targeted'));
            }

            if ($locked->is_submitted) {
                throw new AuthorizationException(__('pkl.industri.already_submitted'));
            }

            $jawaban = app(ScoreService::class)->normalize($locked->definition, $input);

            $locked->update([
                'jawaban' => $jawaban,
                'is_submitted' => true,
                'submitted_at' => now(),
            ]);

            $this->audit($locked, 'nilai_industri_dikunci', null, 'terkunci', [
                'nilai_total' => $jawaban['nilai_total'] ?? null,
                'company_id' => $locked->company_id,
            ], 'Nilai industri disimpan dan dikunci');

            return $locked->refresh();
        });
    }
}
