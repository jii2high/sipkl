<?php

namespace App\Filament\App\Pages;

use App\Actions\Bimbingan\MarkBagianAkhir;
use App\Actions\Bimbingan\SubmitBimbingan;
use App\Actions\Pengesahan\UploadRevisiLaporan;
use App\Enums\PengajuanStatus;
use App\Enums\Role;
use App\Models\BimbinganSubmission;
use App\Models\DocumentSignature;
use App\Models\LaporanVersion;
use App\Models\PengajuanSiswa;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Validation\ValidationException;
use Livewire\WithFileUploads;

class BimbinganSaya extends Page
{
    use WithFileUploads;

    protected string $view = 'filament.app.pages.bimbingan-saya';

    protected static ?string $navigationLabel = 'Bimbingan';

    protected static ?string $title = 'Bimbingan Laporan';

    protected static ?int $navigationSort = 4;

    public mixed $file = null;

    public ?string $keterangan = null;

    public mixed $revisionFile = null;

    public static function canAccess(): bool
    {
        return session('active_role') === Role::Siswa->value;
    }

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

    public function uploadRevision(): void
    {
        $pengajuan = $this->pengajuan();

        if ($pengajuan === null) {
            return;
        }

        $this->validate(['revisionFile' => ['required', 'file']]);
        $step = $this->rejectedSignature()?->step_order;

        try {
            app(UploadRevisiLaporan::class)->handle(auth()->user(), $pengajuan, $this->revisionFile);
        } catch (ValidationException $exception) {
            $this->showValidation($exception);

            return;
        }

        $this->reset('revisionFile');
        Notification::make()->title(__('pkl.pengesahan.revision_uploaded', ['step' => $step ?? '']))->success()->send();
    }

    public function submit(): void
    {
        $pengajuan = $this->pengajuan();

        if ($pengajuan === null) {
            Notification::make()->title(__('pkl.bimbingan.only_sedang_pkl'))->danger()->send();

            return;
        }

        $this->validate([
            'file' => ['required', 'file'],
            'keterangan' => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            app(SubmitBimbingan::class)->handle(auth()->user(), $pengajuan, $this->file, $this->keterangan);
        } catch (ValidationException $exception) {
            $this->showValidation($exception);

            return;
        }

        $this->reset('file', 'keterangan');
        Notification::make()->title(__('pkl.bimbingan.uploaded'))->success()->send();
    }

    public function markFinal(int $id): void
    {
        $submission = BimbinganSubmission::query()->findOrFail($id);

        try {
            app(MarkBagianAkhir::class)->handle(auth()->user(), $submission);
        } catch (ValidationException $exception) {
            $this->showValidation($exception);

            return;
        }

        Notification::make()->title(__('pkl.bimbingan.bagian_akhir_marked'))->success()->send();
    }

    private function showValidation(ValidationException $exception): void
    {
        foreach ($exception->errors() as $field => $messages) {
            $this->addError($field, $messages[0]);
        }

        Notification::make()
            ->title($exception->errors()[array_key_first($exception->errors())][0] ?? '')
            ->danger()
            ->send();
    }
}
