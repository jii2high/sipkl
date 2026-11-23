<?php

namespace App\Actions\Industri;

use App\Actions\Action;
use App\Models\FormSubmission;
use App\Models\MagicLink;
use App\Models\User;
use App\Notifications\IndustryLinkNotification;
use App\Services\MagicLinkService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * Terbitkan pengganti tautan penilaian industri: tautan lama dicabut, tautan
 * baru berlaku 7 hari (BR-F11-1). Ditolak bila submission sudah final.
 */
final class ReissueIndustryLink extends Action
{
    /**
     * @return array{link: MagicLink, token: string, url: string}
     */
    public function handle(mixed ...$arguments): array
    {
        [$submission, $actor, $reason] = $arguments;

        if (! $submission instanceof FormSubmission || ! $actor instanceof User) {
            throw new RuntimeException('Submission dan aktor wajib diisi.');
        }

        Gate::forUser($actor)->authorize('reissueIndustryLink', $submission);

        if ($submission->is_submitted) {
            throw ValidationException::withMessages(['tautan' => __('pkl.industri.submission_final')]);
        }

        $reason = trim((string) $reason);

        if (mb_strlen($reason) < 10) {
            throw ValidationException::withMessages(['alasan' => __('pkl.industri.revoke_reason_min')]);
        }

        $link = app(MagicLinkService::class)->activeFor(
            MagicLinkService::TYPE_INDUSTRY_ASSESSMENT,
            (int) $submission->getKey(),
        );

        if ($link === null) {
            throw ValidationException::withMessages(['tautan' => __('pkl.industri.link_missing')]);
        }

        $issued = app(MagicLinkService::class)->reissue($link, null, $actor, $reason);
        $url = route('eksternal.industri', ['token' => $issued['token']]);

        Notification::route('mail', trim((string) $submission->company?->pembimbing_industri_email))
            ->notify(new IndustryLinkNotification($url, (string) $submission->company?->nama));

        return $issued + ['url' => $url];
    }
}
