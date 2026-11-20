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

/**
 * Terbitkan ulang tautan step 3/4 yang kedaluwarsa/dicabut (F7). Alasan wajib dan
 * ikut tercatat di audit; revoke lama + terbit baru berjalan satu transaksi.
 */
final class ReissueSignerLink extends IssueSignerLink
{
    /**
     * @return array{link: MagicLink, token: string}
     */
    public function handle(mixed ...$arguments): array
    {
        [$actor, $signature, $reason] = $arguments;

        if (! $actor instanceof User || ! $signature instanceof DocumentSignature || ! is_string($reason)) {
            throw ValidationException::withMessages(['alasan' => __('pkl.pengesahan.reason_min')]);
        }

        $reason = trim($reason);

        if (mb_strlen($reason) < 10) {
            throw ValidationException::withMessages(['alasan' => __('pkl.pengesahan.reason_min')]);
        }

        Gate::forUser($actor)->authorize('issueLink', $signature);

        $this->assertReady($signature);

        $type = $this->typeFor($signature);
        $links = app(MagicLinkService::class);
        $active = $links->activeFor($type, (int) $signature->getKey());

        if ($active === null) {
            throw ValidationException::withMessages(['tautan' => __('pkl.pengesahan.link_not_active')]);
        }

        $email = $this->verifiedSignerEmail($signature);

        $issued = $this->transaction(fn (): array => $links->reissue($active, null, $actor, $reason));

        Notification::route('mail', $email)->notify(new SignerLinkNotification(
            url: route('eksternal.masuk', ['token' => $issued['token']]),
            stepLabel: self::STEP_LABELS[(int) $signature->step_order],
        ));

        return $issued;
    }
}
