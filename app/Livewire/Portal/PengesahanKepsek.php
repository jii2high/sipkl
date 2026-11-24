<?php

namespace App\Livewire\Portal;

use App\Actions\Pengesahan\BulkApproveSignatures;
use App\Actions\Pengesahan\PengesahanAction;
use App\Actions\Pengesahan\RejectStep;
use App\Actions\Pengesahan\SignStep;
use App\Models\DocumentSignature;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Portal kepsek `/kepsek` (R4.3): tanda tangan step 5 laporan final, satu per
 * satu atau sekaligus. Antrean dibatasi ke step 5 saja; keputusan dan guard
 * urutan tetap di Action Pengesahan.
 */
#[Layout('components.layouts.portal')]
#[Title('Pengesahan laporan')]
class PengesahanKepsek extends Component
{
    /** @var array<int, int|string> */
    public array $selected = [];

    public ?int $rejectingId = null;

    public string $rejectNote = '';

    /** Pesan singkat aksi terakhir; pengganti toast panel. */
    public ?string $flash = null;

    public string $flashTone = 'info';

    /** @var array{approved: array<int, int>, failed: array<int, array{id: int, alasan: string}>}|null */
    public ?array $bulkResult = null;

    /** @return array<int, DocumentSignature> */
    public function signatures(): array
    {
        return DocumentSignature::query()
            ->where('status', 'pending')
            ->where('step_order', 5)
            ->with(['pengajuanSiswa.siswa', 'pengajuanSiswa.company', 'laporanVersion'])
            ->orderBy('id')
            ->get()
            ->all();
    }

    public function stepLabel(int $order): string
    {
        return $order.'. '.(PengesahanAction::STEP_LABELS[$order] ?? '');
    }

    /** Nama siswa pada laporan yang gagal diproses bulk; id dipakai sebagai fallback. */
    public function studentName(int $id): string
    {
        $signature = DocumentSignature::query()->with('pengajuanSiswa.siswa')->find($id);

        return $signature?->pengajuanSiswa?->siswa?->name ?? '#'.$id;
    }

    public function approve(int $id): void
    {
        $this->bulkResult = null;

        if ($this->run(
            fn () => app(SignStep::class)->handle(DocumentSignature::query()->findOrFail($id), auth()->user()),
            __('pkl.pengesahan.signed', ['step' => 5]),
        )) {
            $this->forgetSelected($id);
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
        $this->bulkResult = null;

        try {
            $this->validate([
                'rejectNote' => ['required', 'string', 'min:10', 'max:2000'],
            ], attributes: ['rejectNote' => 'Catatan penolakan']);
        } catch (ValidationException $exception) {
            $this->reportValidation($exception);

            return;
        }

        if ($this->run(
            fn () => app(RejectStep::class)->handle(
                DocumentSignature::query()->findOrFail($id),
                $this->rejectNote,
                auth()->user(),
            ),
            __('pkl.pengesahan.rejected'),
        )) {
            $this->forgetSelected($id);
            $this->cancelReject();
        }
    }

    public function toggleAll(): void
    {
        $this->bulkResult = null;
        $ids = array_map(fn (DocumentSignature $signature): int => (int) $signature->getKey(), $this->signatures());
        $chosen = array_map('intval', $this->selected);

        $this->selected = $ids !== [] && count(array_intersect($chosen, $ids)) === count($ids) ? [] : $ids;
    }

    public function approveSelected(): void
    {
        if ($this->selected === []) {
            return;
        }

        $result = app(BulkApproveSignatures::class)->handle(auth()->user(), $this->selected);
        $this->bulkResult = $result;

        foreach ($result['approved'] as $id) {
            $this->forgetSelected((int) $id);
        }

        $this->notify(
            count($result['approved']).' laporan disetujui, '.count($result['failed']).' gagal.',
            $result['failed'] === [] ? 'success' : 'warning',
        );
    }

    /** Buang satu id dari pilihan setelah aksinya benar-benar berhasil. */
    private function forgetSelected(int $id): void
    {
        $this->selected = array_values(array_filter(
            array_map('intval', $this->selected),
            fn (int $selected): bool => $selected !== $id,
        ));
    }

    private function run(callable $action, string $success): bool
    {
        try {
            $action();
        } catch (ValidationException $exception) {
            $this->notify(
                (string) (collect($exception->errors())->flatten()->first() ?? __('pkl.pengesahan.not_authorized')),
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

    private function reportValidation(ValidationException $exception): void
    {
        foreach ($exception->errors() as $field => $messages) {
            $this->addError($field, $messages[0]);
        }

        $this->notify($exception->errors()['rejectNote'][0] ?? __('pkl.pengesahan.reject_note_min'), 'danger');
    }

    public function render(): mixed
    {
        return view('livewire.portal.pengesahan-kepsek');
    }
}
