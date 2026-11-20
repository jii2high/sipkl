<?php

namespace App\Actions\Bimbingan;

use App\Actions\Action;
use App\Enums\BimbinganStatus;
use App\Enums\Role;
use App\Models\BimbinganSubmission;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * Guru memberi feedback bimbingan: `revisi` atau `diterima` (F6). Idempoten:
 * baris yang sudah dinilai tidak berubah pada panggilan kedua.
 */
final class GiveFeedback extends Action
{
    public function handle(mixed ...$arguments): BimbinganSubmission
    {
        [$submission, $decision, $note, $guru] = $arguments;

        if (! $submission instanceof BimbinganSubmission || ! $guru instanceof User || ! is_string($decision)) {
            throw new RuntimeException('Bimbingan, keputusan, dan guru wajib diisi.');
        }

        $to = BimbinganStatus::tryFrom($decision);

        if (! in_array($to, [BimbinganStatus::Revisi, BimbinganStatus::Diterima], true)) {
            throw ValidationException::withMessages(['keputusan' => __('pkl.bimbingan.decision_invalid')]);
        }

        Gate::forUser($guru)->authorize('review', $submission);

        return $this->transaction(function () use ($submission, $to, $note, $guru): BimbinganSubmission {
            $locked = BimbinganSubmission::query()->whereKey($submission->getKey())->lockForUpdate()->firstOrFail();

            $from = BimbinganStatus::from((string) $locked->status);

            if ($from === $to) {
                return $locked;
            }

            $from->assertTransitionTo($to, Role::GuruPembimbing->value);

            $this->transitionStatus($locked, $to->value, BimbinganStatus::MenungguFeedback->value, [
                'feedback_note' => $note,
                'reviewed_by' => $guru->getKey(),
                'reviewed_at' => now(),
            ]);

            $this->audit(
                $locked,
                'bimbingan_feedback',
                BimbinganStatus::MenungguFeedback->value,
                $to->value,
                ['catatan' => $note, 'oleh' => $guru->getKey()],
                'Feedback bimbingan diberikan',
            );

            return $locked->refresh();
        });
    }
}
