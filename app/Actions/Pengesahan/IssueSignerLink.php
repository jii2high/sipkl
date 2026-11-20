<?php

namespace App\Actions\Pengesahan;

use App\Models\DocumentSignature;
use App\Models\MagicLink;
use App\Models\User;
use App\Notifications\SignerLinkNotification;
use App\Services\MagicLinkService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * Terbitkan tautan pengesahan step 3/4 (F7, TRD.md §9). Hanya setelah step
 * sebelumnya signed dan hanya untuk signer dengan email terverifikasi.
 */
final class IssueSignerLink extends PengesahanAction
{
    /**
     * @return array{link: MagicLink, token: string}
     */
    public function handle(mixed ...$arguments): array
    {
        [$actor, $signature] = $arguments;

        if (! $actor instanceof User || ! $signature instanceof DocumentSignature) {
            throw new RuntimeException('Aktor dan tanda tangan wajib diisi.');
        }

        Gate::forUser($actor)->authorize('issueLink', $signature);

        $this->assertReady($signature);

        $type = $this->typeFor($signature);
        $links = app(MagicLinkService::class);

        if ($links->activeFor($type, (int) $signature->getKey()) !== null) {
            throw ValidationException::withMessages(['tautan' => __('pkl.pengesahan.link_already_active')]);
        }

        return $this->issueAndSend($links, $signature, $actor, $type);
    }

    protected function assertReady(DocumentSignature $signature): void
    {
        if ($signature->status !== 'pending') {
            throw ValidationException::withMessages(['step' => __('pkl.pengesahan.signature_not_pending')]);
        }

        $this->assertSequence($signature);
    }

    protected function typeFor(DocumentSignature $signature): string
    {
        return match ((int) $signature->step_order) {
            3 => MagicLinkService::TYPE_SIGNER_STEP3,
            4 => MagicLinkService::TYPE_SIGNER_STEP4,
            default => throw ValidationException::withMessages(['step' => __('pkl.pengesahan.external_step_only')]),
        };
    }

    /**
     * @return array{link: MagicLink, token: string}
     */
    protected function issueAndSend(
        MagicLinkService $links,
        DocumentSignature $signature,
        User $actor,
        string $type,
    ): array {
        $email = $this->verifiedSignerEmail($signature);

        $issued = $this->transaction(function () use ($links, $signature, $actor, $type): array {
            $issued = $links->issue($type, (int) $signature->getKey(), null, $actor);

            $this->audit(
                $signature,
                'signer_link_issued',
                null,
                'issued',
                ['step' => $signature->step_order, 'oleh' => $actor->getKey()],
                'Tautan pengesahan step '.$signature->step_order.' diterbitkan',
            );

            return $issued;
        });

        Notification::route('mail', $email)->notify(new SignerLinkNotification(
            url: route('eksternal.masuk', ['token' => $issued['token']]),
            stepLabel: self::STEP_LABELS[(int) $signature->step_order],
        ));

        return $issued;
    }
}
