<?php

namespace App\Actions\Sidang;

use App\Actions\Action;
use App\Models\JadwalSidang;
use App\Models\MagicLink;
use App\Models\User;
use App\Services\ExaminerLinkService;
use App\Services\MagicLinkService;
use App\Support\JakartaTime;
use Illuminate\Support\Facades\Gate;
use RuntimeException;

/**
 * Terbitkan tautan + PIN 4 digit untuk penguji (F9 alur 2). Tautan berlaku sampai
 * akhir hari sidang (Jakarta, BR-F9-4). Penerbitan selalu mencabut tautan aktif
 * sebelumnya sehingga PIN lama tidak berlaku lagi.
 */
final class IssueExaminerLink extends Action
{
    public function __construct(private readonly ExaminerLinkService $links) {}

    /**
     * @return array{link: MagicLink, token: string, pin: string, url: string, wa_url: string}
     */
    public function handle(mixed ...$arguments): array
    {
        [$jadwal, $kaprog] = $arguments;

        if (! $jadwal instanceof JadwalSidang || ! $kaprog instanceof User) {
            throw new RuntimeException('Jadwal dan Kaprog wajib diisi.');
        }

        Gate::forUser($kaprog)->authorize('issueLink', $jadwal);

        return $this->transaction(function () use ($jadwal, $kaprog): array {
            $pin = str_pad((string) random_int(0, 9999), (int) config('pkl.examiner.pin_digits', 4), '0', STR_PAD_LEFT);

            ['link' => $link, 'token' => $token] = app(MagicLinkService::class)->issue(
                MagicLinkService::TYPE_EXAMINER_SIDANG,
                (int) $jadwal->getKey(),
                $pin,
                $kaprog,
                null,
                null,
                JakartaTime::endOfDay($jadwal->tanggal),
            );

            $url = $this->links->url($token);
            $waUrl = $this->links->deliver($jadwal->externalExaminer, $url, $pin);

            $this->audit(
                $jadwal,
                'tautan_penguji_diterbitkan',
                null,
                'aktif',
                ['magic_link_id' => $link->getKey(), 'examiner_id' => $jadwal->external_examiner_id],
                'Tautan penguji diterbitkan',
            );

            return ['link' => $link, 'token' => $token, 'pin' => $pin, 'url' => $url, 'wa_url' => $waUrl];
        });
    }
}
