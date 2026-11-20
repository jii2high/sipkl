<?php

namespace App\Actions\Pengesahan;

use App\Enums\SignatureStatus;
use App\Models\DocumentSignature;
use App\Models\ExternalSession;
use App\Models\LaporanVersion;
use App\Models\User;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * Tandatangani satu step (F7). Step N hanya boleh setelah N-1 signed; tanda
 * tangan kedua idempoten. Step 5 menutup rantai dan menandai laporan legit.
 */
final class SignStep extends PengesahanAction
{
    public function handle(mixed ...$arguments): DocumentSignature
    {
        [$signature] = $arguments;
        $actor = $arguments[1] ?? null;
        $session = $arguments[2] ?? null;

        if (! $signature instanceof DocumentSignature
            || ($actor !== null && ! $actor instanceof User)
            || ($session !== null && ! $session instanceof ExternalSession)) {
            throw new RuntimeException('Tanda tangan, aktor, atau sesi eksternal tidak valid.');
        }

        $this->authorizeActor($signature, $actor, $session, 'sign');

        return $this->transaction(function () use ($signature, $actor): DocumentSignature {
            $locked = DocumentSignature::query()->whereKey($signature->getKey())->lockForUpdate()->firstOrFail();
            $from = SignatureStatus::from((string) $locked->status);

            if ($from === SignatureStatus::Signed) {
                return $locked;
            }

            if ($from === SignatureStatus::Rejected) {
                throw ValidationException::withMessages(['step' => __('pkl.pengesahan.already_rejected')]);
            }

            $this->assertSequence($locked);
            $from->assertTransitionTo(SignatureStatus::Signed, (string) $locked->signer_type);

            $attributes = ['signed_at' => now()];

            // CHECK ck_sign_internal: step 1/2/5 wajib punya pencatat internal.
            if ($actor !== null) {
                $attributes['signer_user_id'] = $actor->getKey();
            }

            $this->transitionStatus($locked, SignatureStatus::Signed->value, SignatureStatus::Pending->value, $attributes);

            $this->audit(
                $locked,
                'ttd_step_signed',
                SignatureStatus::Pending->value,
                SignatureStatus::Signed->value,
                ['step' => $locked->step_order, 'oleh' => $actor?->getKey()],
                'TTD step '.$locked->step_order.' ditandatangani',
            );

            $this->openNext($locked);

            return $locked->refresh();
        });
    }

    /** Buka step berikutnya pada versi yang sama; step 5 menutup rantai. */
    private function openNext(DocumentSignature $signature): void
    {
        $step = (int) $signature->step_order;

        if ($step >= 5) {
            LaporanVersion::query()->whereKey($signature->laporan_version_id)
                ->update(['is_legit' => true]);

            return;
        }

        $next = $step + 1;
        $company = $signature->pengajuanSiswa?->company;

        $attributes = [
            'pengajuan_siswa_id' => $signature->pengajuan_siswa_id,
            'laporan_version_id' => $signature->laporan_version_id,
            'step_order' => $next,
            'signer_type' => self::STEP_TYPES[$next],
            'status' => SignatureStatus::Pending->value,
        ];

        if ($next === 3 || $next === 4) {
            $attributes['signer_external_name'] = $next === 3
                ? $company?->pembimbing_industri_nama
                : $company?->kepala_industri_nama;
            $attributes['signer_external_contact'] = $next === 3
                ? $company?->pembimbing_industri_email
                : $company?->kepala_industri_email;
        }

        $opened = DocumentSignature::query()->firstOrCreate(
            ['laporan_version_id' => $signature->laporan_version_id, 'step_order' => $next],
            $attributes,
        );

        if (($next === 3 || $next === 4) && $opened->wasRecentlyCreated) {
            $email = $next === 3
                ? $company?->pembimbing_industri_email
                : $company?->kepala_industri_email;
            $verified = $next === 3
                ? $company?->pembimbing_industri_email_verified_at
                : $company?->kepala_industri_email_verified_at;

            if (! is_string($email) || trim($email) === '' || $verified === null) {
                $this->notifyHubinHeld($signature, $next);
            }
        }
    }
}
