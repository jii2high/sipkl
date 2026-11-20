<?php

namespace App\Actions\Sidang;

use App\Actions\Action;
use App\Models\FormSubmission;
use App\Models\SidangResultsSummary;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * Status kelulusan sidang diisi manual Kaprog (Q-I): pilihan Lulus / Tidak Lulus
 * plus catatan bebas. Ringkasan sidang dipastikan ada lebih dulu.
 */
final class SetKelulusan extends Action
{
    public const LULUS = 'Lulus';

    public const TIDAK_LULUS = 'Tidak Lulus';

    public function handle(mixed ...$arguments): SidangResultsSummary
    {
        [$submission, $status, $catatan, $kaprog] = $arguments;

        if (! $submission instanceof FormSubmission || ! $kaprog instanceof User) {
            throw new RuntimeException('Submission dan Kaprog wajib diisi.');
        }

        if ($status !== null && ! in_array($status, [self::LULUS, self::TIDAK_LULUS], true)) {
            throw ValidationException::withMessages(['status' => __('pkl.sidang.kelulusan_invalid')]);
        }

        Gate::forUser($kaprog)->authorize('setKelulusan', $submission);

        return $this->transaction(function () use ($submission, $status, $catatan, $kaprog): SidangResultsSummary {
            $summary = app(SyncSidangResultsSummary::class)->handle(
                FormSubmission::query()->whereKey($submission->getKey())->firstOrFail(),
            );

            if ($summary === null) {
                throw ValidationException::withMessages(['status' => __('pkl.sidang.summary_missing')]);
            }

            $catatan = $catatan === null ? null : trim((string) $catatan);

            $summary->update([
                'status_kelulusan' => $status,
                'catatan_kelulusan' => $catatan === '' ? null : $catatan,
            ]);

            $this->audit(
                $summary,
                'kelulusan_sidang_ditetapkan',
                null,
                (string) $status,
                ['form_submission_id' => $submission->getKey(), 'oleh' => $kaprog->getKey()],
                'Status kelulusan sidang ditetapkan',
            );

            return $summary->refresh();
        });
    }
}
