<?php

namespace App\Actions\Pengesahan;

use App\Actions\Action;
use App\Exceptions\DocumentIssuanceHeld;
use App\Models\DocumentSignature;
use App\Models\ExternalSession;
use App\Models\User;
use App\Notifications\Notification;
use App\Services\ExternalOtpService;
use App\Services\MagicLinkService;
use App\Services\NotificationService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/**
 * Dasar Action rantai 5-TTD (F7): urutan step, otorisasi internal/eksternal, dan
 * notifikasi Hubin saat signer industri belum terverifikasi.
 */
abstract class PengesahanAction extends Action
{
    /** @var array<int, string> */
    public const STEP_LABELS = [
        1 => 'Guru Pembimbing',
        2 => 'Kaprog',
        3 => 'Pembimbing Industri',
        4 => 'Kepala Industri',
        5 => 'Kepsek',
    ];

    /** @return array<int, string> */
    public const STEP_TYPES = [
        1 => 'guru_pembimbing',
        2 => 'kaprog',
        3 => 'pembimbing_industri',
        4 => 'kepala_industri',
        5 => 'kepsek',
    ];

    /** Step N hanya sah setelah step N-1 berstatus signed (TRD.md §5.4). */
    protected function assertSequence(DocumentSignature $signature): void
    {
        $step = (int) $signature->step_order;

        if ($step <= 1) {
            return;
        }

        $previousSigned = DocumentSignature::query()
            ->where('pengajuan_siswa_id', $signature->pengajuan_siswa_id)
            ->where('step_order', $step - 1)
            ->where('status', 'signed')
            ->exists();

        if (! $previousSigned) {
            throw ValidationException::withMessages([
                'step' => __('pkl.pengesahan.previous_unsigned', ['step' => $step - 1]),
            ]);
        }
    }

    /**
     * Aktor internal memakai Policy; aktor eksternal hanya boleh lewat sesi OTP
     * yang berasal dari link untuk step 3/4 milik baris tanda tangan ini.
     */
    protected function authorizeActor(
        DocumentSignature $signature,
        ?User $actor,
        ?ExternalSession $session,
        string $ability,
    ): void {
        if ($actor !== null) {
            Gate::forUser($actor)->authorize($ability, $signature);

            return;
        }

        if ($session !== null
            && app(ExternalOtpService::class)->isUsable($session)
            && in_array((int) $signature->step_order, [3, 4], true)) {
            $link = $session->magicLink;
            $expected = (int) $signature->step_order === 3
                ? MagicLinkService::TYPE_SIGNER_STEP3
                : MagicLinkService::TYPE_SIGNER_STEP4;

            if ($link !== null
                && $link->type === $expected
                && (int) $link->reference_id === (int) $signature->getKey()) {
                return;
            }
        }

        throw new AuthorizationException(__('pkl.eksternal.invalid_link'));
    }

    /** Email signer yang sudah terverifikasi, atau tahan langkah + beri tahu Hubin. */
    protected function verifiedSignerEmail(DocumentSignature $signature): string
    {
        $company = $signature->pengajuanSiswa?->company;
        $step = (int) $signature->step_order;

        $email = match ($step) {
            3 => $company?->pembimbing_industri_email,
            4 => $company?->kepala_industri_email,
            default => null,
        };

        $verifiedAt = match ($step) {
            3 => $company?->pembimbing_industri_email_verified_at,
            4 => $company?->kepala_industri_email_verified_at,
            default => null,
        };

        if ($company === null || ! is_string($email) || trim($email) === '' || $verifiedAt === null) {
            $this->notifyHubinHeld($signature, $step);

            throw DocumentIssuanceHeld::noVerifiedSigner();
        }

        return $email;
    }

    protected function notifyHubinHeld(DocumentSignature $signature, int $step): void
    {
        $student = $signature->pengajuanSiswa?->siswa?->name ?? 'Siswa';

        User::query()
            ->whereHas('roles', fn ($query) => $query->where('role', 'hubin'))
            ->get()
            ->each(fn (User $hubin) => app(NotificationService::class)->send(
                $hubin,
                new Notification(
                    'Langkah TTD ditahan',
                    'Rantai pengesahan '.$student.' mencapai step '.$step.' ('.self::STEP_LABELS[$step].'), '
                        .'tetapi email signer industri belum terverifikasi. Lengkapi data perusahaan lalu terbitkan tautan.',
                    url('/hubin'),
                ),
                ['related' => $signature],
            ));
    }

    /**
     * Status 5 step untuk satu pengajuan: baris terbaru per step (versi terakhir).
     *
     * @return array<int, array{label: string, status: string, waiting: ?string, note: ?string}>
     */
    public static function stepsFor(int $pengajuanSiswaId): array
    {
        $rows = DocumentSignature::query()
            ->where('pengajuan_siswa_id', $pengajuanSiswaId)
            ->get()
            ->sortBy([['laporan_version_id', 'desc'], ['step_order', 'asc']])
            ->unique('step_order')
            ->keyBy('step_order');

        $steps = [];

        foreach (self::STEP_LABELS as $order => $label) {
            $row = $rows->get($order);
            $status = $row?->status ?? 'empty';

            $steps[] = [
                'label' => $label,
                'status' => $status,
                'waiting' => $status === 'pending' ? 'Menunggu '.$label : null,
                'note' => $row?->rejection_note,
            ];
        }

        return $steps;
    }
}
