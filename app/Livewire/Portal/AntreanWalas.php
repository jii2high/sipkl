<?php

namespace App\Livewire\Portal;

use App\Actions\Pengajuan\RejectByWalas;
use App\Actions\Pengajuan\SignByWalas;
use App\Enums\PengajuanStatus;
use App\Enums\Role;
use App\Models\PengajuanSiswa;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Portal walas `/walas` (R4.4): antrean Gate 2 kelas yang diampu. Tanda tangan
 * dan penolakan tetap diputuskan di Action Pengajuan; komponen ini hanya UI.
 */
#[Layout('components.layouts.portal')]
#[Title('Antrean kelas')]
class AntreanWalas extends Component
{
    public ?int $rejectingId = null;

    public string $rejectNote = '';

    /** Pesan singkat aksi terakhir; pengganti toast panel. */
    public ?string $flash = null;

    public string $flashTone = 'info';

    /** @return array<int, PengajuanSiswa> */
    public function queue(): array
    {
        return PengajuanSiswa::query()
            ->visibleTo(auth()->user(), Role::Walas->value)
            ->where('status', PengajuanStatus::MenungguTtdWalas->value)
            ->with(['siswa:id,name', 'company:id,nama'])
            ->orderBy('updated_at')
            ->get()
            ->all();
    }

    public function approve(int $id): void
    {
        $pengajuan = $this->find($id);

        if ($this->run(
            fn () => app(SignByWalas::class)->handle($pengajuan, auth()->user()),
            __('pkl.pengajuan.signed_walas'),
        )) {
            $this->cancelReject();
        }
    }

    public function startReject(int $id): void
    {
        $this->rejectingId = $id;
        $this->rejectNote = '';
        $this->resetErrorBag('rejectNote');
    }

    public function cancelReject(): void
    {
        $this->rejectingId = null;
        $this->rejectNote = '';
        $this->resetErrorBag('rejectNote');
    }

    public function reject(int $id): void
    {
        $min = (int) config('pkl.rejection_note.min_length');

        try {
            $this->validate([
                'rejectNote' => ['required', 'string', 'min:'.$min, 'max:2000'],
            ], [
                'rejectNote.required' => __('pkl.rejection_note.required'),
                'rejectNote.min' => __('pkl.rejection_note.min', ['min' => $min]),
            ], ['rejectNote' => 'Catatan penolakan']);
        } catch (ValidationException $exception) {
            $this->reportNoteError($exception);

            return;
        }

        $pengajuan = $this->find($id);

        if ($this->run(
            fn () => app(RejectByWalas::class)->handle($pengajuan, auth()->user(), $this->rejectNote),
            __('pkl.pengajuan.rejected_walas'),
        )) {
            $this->cancelReject();
        }
    }

    private function find(int $id): PengajuanSiswa
    {
        return PengajuanSiswa::query()
            ->visibleTo(auth()->user(), Role::Walas->value)
            ->findOrFail($id);
    }

    private function run(callable $action, string $success): bool
    {
        try {
            $action();
        } catch (ValidationException $exception) {
            $this->notify(
                (string) (collect($exception->errors())->flatten()->first() ?? 'Pengajuan belum dapat diproses.'),
                'danger',
            );

            return false;
        }

        $this->notify($success, 'success');

        return true;
    }

    private function notify(string $message, string $tone): void
    {
        $this->flash = $message;
        $this->flashTone = $tone;
    }

    private function reportNoteError(ValidationException $exception): void
    {
        foreach ($exception->errors() as $field => $messages) {
            $this->addError($field, $messages[0]);
        }

        $this->notify($exception->errors()['rejectNote'][0] ?? __('pkl.rejection_note.required'), 'danger');
    }

    public function render(): mixed
    {
        return view('livewire.portal.antrean-walas');
    }
}
