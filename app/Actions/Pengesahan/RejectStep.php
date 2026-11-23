<?php

namespace App\Actions\Pengesahan;

use App\Enums\SignatureStatus;
use App\Models\DocumentSignature;
use App\Models\ExternalSession;
use App\Models\User;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * Tolak satu step dengan catatan minimal 10 karakter (F7). Step sebelumnya tidak
 * diubah; siswa harus mengunggah revisi untuk melanjutkan dari step yang menolak.
 */
final class RejectStep extends PengesahanAction
{
    public function handle(mixed ...$arguments): DocumentSignature
    {
        [$signature, $note] = $arguments;
        $actor = $arguments[2] ?? null;
        $session = $arguments[3] ?? null;

        if (! $signature instanceof DocumentSignature
            || ! is_string($note)
            || ($actor !== null && ! $actor instanceof User)
            || ($session !== null && ! $session instanceof ExternalSession)) {
            throw new RuntimeException('Tanda tangan, catatan, aktor, atau sesi eksternal tidak valid.');
        }

        $note = trim($note);

        if (mb_strlen($note) < 10) {
            throw ValidationException::withMessages(['catatan' => __('pkl.pengesahan.reject_note_min')]);
        }

        $this->authorizeActor($signature, $actor, $session, 'reject');

        return $this->transaction(function () use ($signature, $note, $actor): DocumentSignature {
            $locked = DocumentSignature::query()->whereKey($signature->getKey())->lockForUpdate()->firstOrFail();
            $from = SignatureStatus::from((string) $locked->status);

            if ($from === SignatureStatus::Rejected) {
                return $locked;
            }

            if ($from === SignatureStatus::Signed) {
                throw ValidationException::withMessages(['step' => __('pkl.pengesahan.already_signed')]);
            }

            $this->assertSequence($locked);
            $from->assertTransitionTo(SignatureStatus::Rejected, (string) $locked->signer_type);

            $attributes = ['rejection_note' => $note];

            // CHECK ck_sign_internal: step 1/2/5 yang rejected wajib punya pencatat internal.
            if ($actor !== null) {
                $attributes['signer_user_id'] = $actor->getKey();
            }

            $this->transitionStatus($locked, SignatureStatus::Rejected->value, SignatureStatus::Pending->value, $attributes);

            $this->audit(
                $locked,
                'ttd_step_rejected',
                SignatureStatus::Pending->value,
                SignatureStatus::Rejected->value,
                ['step' => $locked->step_order, 'catatan' => $note, 'oleh' => $actor?->getKey()],
                'TTD step '.$locked->step_order.' ditolak',
            );

            return $locked->refresh();
        });
    }
}
