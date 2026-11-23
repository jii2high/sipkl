<?php

namespace App\Livewire\Portal;

use App\Actions\Bimbingan\MarkBagianAkhir;
use App\Actions\Bimbingan\SubmitBimbingan;
use App\Actions\Pengesahan\UploadRevisiLaporan;
use App\Enums\PengajuanStatus;
use App\Models\BimbinganSubmission;
use App\Models\DocumentSignature;
use App\Models\LaporanVersion;
use App\Models\PengajuanSiswa;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Portal siswa `/siswa/bimbingan` (R4.2): unggah bimbingan, progres interaksi,
 * revisi laporan setelah step ditolak, dan penanda bagian akhir. Semua guard
 * tetap di Action domain; kelas ini hanya mengatur state UI.
 */
#[Layout('components.layouts.portal')]
#[Title('Bimbingan laporan')]
class BimbinganSiswa extends Component
{
    use WithFileUploads;

    public mixed $file = null;

    public ?string $keterangan = null;

    public mixed $revisionFile = null;

    /** Pesan singkat aksi terakhir; pengganti toast panel. */
    public ?string $flash = null;

    public string $flashTone = 'info';

    public function pengajuan(): ?PengajuanSiswa
    {
        return PengajuanSiswa::query()
            ->where('siswa_id', auth()->id())
            ->where('status', PengajuanStatus::SedangPkl->value)
            ->latest('id')
            ->first();
    }

    /** @return array<int, BimbinganSubmission> */
    public function submissions(): array
    {
        $pengajuan = $this->pengajuan();

        if ($pengajuan === null) {
            return [];
        }

        return BimbinganSubmission::query()
            ->where('pengajuan_siswa_id', $pengajuan->getKey())
            ->where('is_archived', false)
            ->latest('id')
            ->get()
            ->all();
    }

    /** @return array{count: int, min: int} */
    public function progress(): array
    {
        $pengajuan = $this->pengajuan();
        $min = (int) config('pkl.bimbingan.min_interactions');

        $count = $pengajuan === null ? 0 : BimbinganSubmission::query()
            ->where('pengajuan_siswa_id', $pengajuan->getKey())
            ->where('is_archived', false)
            ->count();

        return ['count' => $count, 'min' => $min];
    }

    /**
     * Versi laporan terakhir dan langkah yang menolaknya (F7). Null bila siswa
     * belum punya laporan final atau tidak ada penolakan pada versi terakhir.
     */
    public function rejectedSignature(): ?DocumentSignature
    {
        $pengajuan = $this->pengajuan();

        if ($pengajuan === null) {
            return null;
        }

        $latest = LaporanVersion::query()
            ->where('pengajuan_siswa_id', $pengajuan->getKey())
            ->orderByDesc('version_number')
            ->first();

        return $latest === null ? null : DocumentSignature::query()
            ->where('laporan_version_id', $latest->getKey())
            ->where('status', 'rejected')
            ->first();
    }

    public function submit(): void
    {
        $this->flash = null;
        $pengajuan = $this->pengajuan();

        if ($pengajuan === null) {
            $this->notify(__('pkl.bimbingan.only_sedang_pkl'), 'danger');

            return;
        }

        try {
            $this->validate([
                'file' => ['required', 'file'],
                'keterangan' => ['nullable', 'string', 'max:2000'],
            ]);
        } catch (ValidationException $exception) {
            $this->reportValidation($exception);

            return;
        }

        try {
            app(SubmitBimbingan::class)->handle(auth()->user(), $pengajuan, $this->file, $this->keterangan);
        } catch (ValidationException $exception) {
            $this->reportValidation($exception);

            return;
        }

        $this->reset('file', 'keterangan');
        $this->notify(__('pkl.bimbingan.uploaded'), 'success');
    }

    public function uploadRevision(): void
    {
        $this->flash = null;
        $pengajuan = $this->pengajuan();

        if ($pengajuan === null) {
            return;
        }

        try {
            $this->validate(['revisionFile' => ['required', 'file']]);
        } catch (ValidationException $exception) {
            $this->reportValidation($exception);

            return;
        }

        $step = $this->rejectedSignature()?->step_order;

        try {
            app(UploadRevisiLaporan::class)->handle(auth()->user(), $pengajuan, $this->revisionFile);
        } catch (ValidationException $exception) {
            $this->reportValidation($exception);

            return;
        }

        $this->reset('revisionFile');
        $this->notify(__('pkl.pengesahan.revision_uploaded', ['step' => $step ?? '']), 'success');
    }

    public function markFinal(int $id): void
    {
        $submission = BimbinganSubmission::query()->findOrFail($id);

        try {
            app(MarkBagianAkhir::class)->handle(auth()->user(), $submission);
        } catch (ValidationException $exception) {
            $this->reportValidation($exception);

            return;
        }

        $this->notify(__('pkl.bimbingan.bagian_akhir_marked'), 'success');
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

        $first = $exception->errors()[array_key_first($exception->errors())][0] ?? 'Bimbingan belum dapat diproses.';

        $this->notify($first, 'danger');
    }

    public function render(): mixed
    {
        return view('livewire.portal.bimbingan-siswa');
    }
}
