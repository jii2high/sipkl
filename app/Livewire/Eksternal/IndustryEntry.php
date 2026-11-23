<?php

namespace App\Livewire\Eksternal;

use App\Exceptions\OtpException;
use App\Models\ExternalSession;
use App\Models\FormSubmission;
use App\Models\MagicLink;
use App\Services\ExternalOtpService;
use App\Services\MagicLinkService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Pintu masuk tautan penilaian industri (F10): OTP email ke pembimbing industri
 * yang sudah terverifikasi. Kegagalan apa pun memakai pesan netral yang sama.
 */
#[Layout('components.layouts.eksternal')]
#[Title('Penilaian industri')]
class IndustryEntry extends Component
{
    public bool $invalid = false;

    public string $code = '';

    public ?string $companyName = null;

    private ?int $linkId = null;

    public function mount(string $token): void
    {
        $link = app(MagicLinkService::class)->resolve($token);

        if ($link === null || $link->type !== MagicLinkService::TYPE_INDUSTRY_ASSESSMENT) {
            $this->invalid = true;

            return;
        }

        $submission = FormSubmission::query()->find($link->reference_id);
        $company = $submission?->company;
        $email = trim((string) $company?->pembimbing_industri_email);

        if ($submission === null || $submission->is_submitted || $email === '') {
            $this->invalid = true;

            return;
        }

        $this->linkId = $link->getKey();
        $this->companyName = $company?->nama;

        app(ExternalOtpService::class)->issue($link, $email);
    }

    public function verify(): mixed
    {
        $this->validate(['code' => ['required', 'digits:6']]);

        $link = $this->linkId === null ? null : MagicLink::query()->find($this->linkId);

        if ($link === null || ! app(MagicLinkService::class)->isUsable($link)) {
            $this->invalid = true;

            return null;
        }

        $session = ExternalSession::query()
            ->where('magic_link_id', $link->getKey())
            ->latest('id')
            ->first();

        if ($session === null) {
            $this->invalid = true;

            return null;
        }

        try {
            $session = app(ExternalOtpService::class)->verify($session, $this->code);
        } catch (OtpException $exception) {
            $this->addError('code', $exception->getMessage());

            return null;
        }

        $links = app(MagicLinkService::class);
        $links->consume($link, $links->fingerprint(request()));
        session(['external_session_id' => $session->getKey()]);

        return $this->redirectRoute('eksternal.industri.nilai', navigate: true);
    }

    public function render(): mixed
    {
        return view('livewire.eksternal.industry-entry');
    }
}
