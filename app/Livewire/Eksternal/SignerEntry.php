<?php

namespace App\Livewire\Eksternal;

use App\Exceptions\OtpException;
use App\Models\DocumentSignature;
use App\Models\ExternalSession;
use App\Models\MagicLink;
use App\Services\ExternalOtpService;
use App\Services\MagicLinkService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Pintu masuk tautan pengesahan step 3/4 (TRD.md §9, F7). Token diverifikasi
 * sebelum OTP dikirim; kegagalan apa pun menampilkan pesan netral yang sama.
 */
#[Layout('components.layouts.eksternal')]
#[Title('Pengesahan laporan')]
class SignerEntry extends Component
{
    public bool $invalid = false;

    public string $code = '';

    public ?int $step = null;

    public ?string $signerName = null;

    private ?int $linkId = null;

    public function mount(string $token): void
    {
        $link = app(MagicLinkService::class)->resolve($token);

        if ($link === null || ! in_array($link->type, [
            MagicLinkService::TYPE_SIGNER_STEP3,
            MagicLinkService::TYPE_SIGNER_STEP4,
        ], true)) {
            $this->invalid = true;

            return;
        }

        $signature = DocumentSignature::query()->find($link->reference_id);
        $email = is_string($signature?->signer_external_contact) ? trim($signature->signer_external_contact) : '';

        if ($signature === null || $signature->status !== 'pending' || $email === '') {
            $this->invalid = true;

            return;
        }

        $this->linkId = $link->getKey();
        $this->step = (int) $signature->step_order;
        $this->signerName = $signature->signer_external_name;

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

        return $this->redirectRoute('eksternal.ttd', navigate: true);
    }

    public function render(): mixed
    {
        return view('livewire.eksternal.signer-entry');
    }
}
