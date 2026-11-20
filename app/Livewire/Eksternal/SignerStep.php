<?php

namespace App\Livewire\Eksternal;

use App\Actions\Pengesahan\PengesahanAction;
use App\Actions\Pengesahan\RejectStep;
use App\Actions\Pengesahan\SignStep;
use App\Models\DocumentSignature;
use App\Models\ExternalSession;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Layar tanda tangan eksternal step 3/4 (F7). Hanya data satu laporan; guard
 * `eksternal` memastikan sesi berasal dari tautan yang sah.
 */
#[Layout('components.layouts.eksternal')]
#[Title('Pengesahan laporan')]
class SignerStep extends Component
{
    public int $signatureId;

    public int $sessionId;

    public string $note = '';

    public bool $done = false;

    public ?string $message = null;

    public function mount(Request $request): void
    {
        $session = $request->attributes->get('external_session');

        if (! $session instanceof ExternalSession) {
            abort(410);
        }

        $signature = DocumentSignature::query()->findOrFail($session->magicLink?->reference_id);

        $this->sessionId = $session->getKey();
        $this->signatureId = $signature->getKey();
    }

    public function signature(): DocumentSignature
    {
        return DocumentSignature::query()->findOrFail($this->signatureId);
    }

    /** @return array<int, array{label: string, status: string, waiting?: string, note?: string}> */
    public function steps(): array
    {
        return PengesahanAction::stepsFor((int) $this->signature()->pengajuan_siswa_id);
    }

    public function approve(): void
    {
        $this->run(fn () => app(SignStep::class)->handle(
            $this->signature(),
            null,
            ExternalSession::query()->findOrFail($this->sessionId),
        ), __('pkl.pengesahan.signed', ['step' => $this->signature()->step_order]));
    }

    public function reject(): void
    {
        $this->validate(['note' => ['required', 'string', 'min:10', 'max:2000']], [
            'note.min' => __('pkl.pengesahan.reject_note_min'),
        ]);

        $this->run(fn () => app(RejectStep::class)->handle(
            $this->signature(),
            $this->note,
            null,
            ExternalSession::query()->findOrFail($this->sessionId),
        ), __('pkl.pengesahan.rejected'));
    }

    private function run(callable $action, string $success): void
    {
        try {
            $action();
        } catch (ValidationException $exception) {
            $this->addError('note', collect($exception->errors())->flatten()->first() ?? '');

            return;
        }

        $this->done = true;
        $this->message = $success;
    }

    public function render(): mixed
    {
        return view('livewire.eksternal.signer-step');
    }
}
