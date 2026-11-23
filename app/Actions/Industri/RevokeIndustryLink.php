<?php

namespace App\Actions\Industri;

use App\Actions\Action;
use App\Models\FormSubmission;
use App\Models\User;
use App\Services\MagicLinkService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * Cabut tautan penilaian industri yang masih aktif (BR-F10-3). Guru pembimbing
 * perusahaan itu atau Hubin (override) boleh melakukannya; alasan wajib dan
 * masuk audit lewat MagicLinkService.
 */
final class RevokeIndustryLink extends Action
{
    public function handle(mixed ...$arguments): mixed
    {
        [$submission, $actor, $reason] = $arguments;

        if (! $submission instanceof FormSubmission || ! $actor instanceof User) {
            throw new RuntimeException('Submission dan aktor wajib diisi.');
        }

        Gate::forUser($actor)->authorize('revokeIndustryLink', $submission);

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

        app(MagicLinkService::class)->revoke($link, $actor, $reason);

        return null;
    }
}
