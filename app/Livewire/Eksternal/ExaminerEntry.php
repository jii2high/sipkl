<?php

namespace App\Livewire\Eksternal;

use App\Exceptions\OtpException;
use App\Models\ExternalSession;
use App\Models\JadwalSidang;
use App\Models\MagicLink;
use App\Services\MagicLinkService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Pintu masuk tautan penguji (F9 alur 2): PIN 4 digit, bukan OTP email. Tiga kali
 * salah membuat tautan tidak berlaku. Tautan yang sah langsung membuka daftar
 * siswa yang dinilai penguji itu pada hari sidang tersebut.
 */
#[Layout('components.layouts.eksternal')]
#[Title('Penilaian sidang')]
class ExaminerEntry extends Component
{
    public bool $invalid = false;

    public string $pin = '';

    public ?string $examinerName = null;

    public ?string $tanggal = null;

    public ?int $linkId = null;

    public function mount(string $token): void
    {
        $link = app(MagicLinkService::class)->resolve($token);

        if ($link === null || ! in_array($link->type, [
            MagicLinkService::TYPE_EXAMINER_SIDANG,
            MagicLinkService::TYPE_EXAMINER_UNLOCK,
        ], true)) {
            $this->invalid = true;

            return;
        }

        $examiner = $link->type === MagicLinkService::TYPE_EXAMINER_SIDANG
            ? JadwalSidang::query()->find($link->reference_id)?->externalExaminer
            : null;

        $this->linkId = $link->getKey();
        $this->examinerName = $examiner?->nama;
        $this->tanggal = $link->type === MagicLinkService::TYPE_EXAMINER_SIDANG
            ? JadwalSidang::query()->find($link->reference_id)?->tanggal?->format('d/m/Y')
            : null;
    }

    public function verify(): mixed
    {
        $this->validate(['pin' => ['required', 'digits:4']]);

        $link = $this->linkId === null ? null : MagicLink::query()->find($this->linkId);

        if ($link === null || ! app(MagicLinkService::class)->isUsable($link)) {
            $this->invalid = true;

            return null;
        }

        try {
            app(MagicLinkService::class)->verifyPin($link, $this->pin);
        } catch (OtpException $exception) {
            $this->addError('pin', $exception->getMessage());

            return null;
        }

        $links = app(MagicLinkService::class);
        $session = ExternalSession::query()->create([
            'magic_link_id' => $link->getKey(),
            'verified_at' => now(),
            'session_expires_at' => $link->expires_at->isBefore(now()->addHours(24))
                ? $link->expires_at
                : now()->addHours(24),
        ]);

        $links->consume($link, $links->fingerprint(request()));
        session(['external_session_id' => $session->getKey()]);

        return $this->redirectRoute('eksternal.penguji.nilai', navigate: true);
    }

    public function render(): mixed
    {
        return view('livewire.eksternal.examiner-entry');
    }
}
