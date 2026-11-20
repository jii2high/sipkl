<?php

namespace App\Actions\Sidang;

use App\Actions\Action;
use App\Models\MagicLink;
use App\Models\NilaiSidangRevision;
use App\Models\User;
use App\Services\ExaminerLinkService;
use App\Services\MagicLinkService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * Kaprog menyetujui permintaan revisi nilai (F9 alur 5): satu tautan unlock + PIN
 * baru yang hanya membuka satu form selama 24 jam (KP-F9-3, KP-F9-4).
 */
final class ApproveUnlock extends Action
{
    public function __construct(private readonly ExaminerLinkService $links) {}

    /**
     * @return array{link: MagicLink, token: string, pin: string, url: string, wa_url: string}
     */
    public function handle(mixed ...$arguments): array
    {
        [$revision, $kaprog] = $arguments;

        if (! $revision instanceof NilaiSidangRevision || ! $kaprog instanceof User) {
            throw new RuntimeException('Revisi dan Kaprog wajib diisi.');
        }

        Gate::forUser($kaprog)->authorize('approve', $revision);

        return $this->transaction(function () use ($revision, $kaprog): array {
            $locked = NilaiSidangRevision::query()->whereKey($revision->getKey())->lockForUpdate()->firstOrFail();

            if ($locked->status !== 'menunggu_persetujuan') {
                throw ValidationException::withMessages(['revisi' => __('pkl.sidang.revision_not_pending')]);
            }

            $pin = str_pad((string) random_int(0, 9999), (int) config('pkl.examiner.pin_digits', 4), '0', STR_PAD_LEFT);

            ['link' => $link, 'token' => $token] = app(MagicLinkService::class)->issue(
                MagicLinkService::TYPE_EXAMINER_UNLOCK,
                (int) $locked->getKey(),
                $pin,
                $kaprog,
            );

            $expiresAt = now()->addHours((int) config('pkl.score_unlock.ttl_hours', 24));

            $locked->update([
                'approved_by_kaprog_id' => $kaprog->getKey(),
                'unlock_magic_link_id' => $link->getKey(),
                'unlock_expires_at' => $expiresAt,
                'status' => 'menunggu_revisi',
            ]);

            $url = $this->links->url($token);
            $examiner = $locked->submission?->jadwalSidang?->externalExaminer;
            $waUrl = $examiner === null ? '' : $this->links->deliver($examiner, $url, $pin);

            $this->audit(
                $locked,
                'revisi_nilai_disetujui',
                'menunggu_persetujuan',
                'menunggu_revisi',
                ['form_submission_id' => $locked->form_submission_id, 'expires_at' => (string) $expiresAt],
                'Revisi nilai disetujui Kaprog',
            );

            return ['link' => $link, 'token' => $token, 'pin' => $pin, 'url' => $url, 'wa_url' => $waUrl];
        });
    }
}
