<?php

namespace App\Filament\App\Pages;

use App\Actions\Bimbingan\ApproveLaporanFinal;
use App\Actions\Bimbingan\GiveFeedback;
use App\Enums\Role;
use App\Models\BimbinganSubmission;
use App\Models\PengajuanSiswa;
use App\Services\PlottingService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Validation\ValidationException;

class BimbinganSiswa extends Page
{
    protected string $view = 'filament.app.pages.bimbingan-siswa';

    protected static ?string $navigationLabel = 'Bimbingan siswa';

    protected static ?string $title = 'Bimbingan Siswa';

    protected static ?int $navigationSort = 5;

    public ?string $note = null;

    public static function canAccess(): bool
    {
        return session('active_role') === Role::GuruPembimbing->value;
    }

    /** @return array<int, PengajuanSiswa> */
    public function students(): array
    {
        return app(PlottingService::class)->studentsForGuru(auth()->user())->all();
    }

    public function giveFeedback(int $submissionId, string $decision): void
    {
        $this->validate(['note' => ['nullable', 'string', 'max:2000']]);

        $submission = BimbinganSubmission::query()->findOrFail($submissionId);

        try {
            app(GiveFeedback::class)->handle($submission, $decision, $this->note, auth()->user());
        } catch (ValidationException $exception) {
            $this->showValidation($exception);

            return;
        }

        $this->reset('note');
        Notification::make()->title(__('pkl.bimbingan.feedback_saved'))->success()->send();
    }

    public function approveFinal(int $pengajuanId, int $submissionId): void
    {
        $pengajuan = PengajuanSiswa::query()->findOrFail($pengajuanId);
        $submission = BimbinganSubmission::query()->findOrFail($submissionId);

        try {
            app(ApproveLaporanFinal::class)->handle(auth()->user(), $pengajuan, $submission);
        } catch (ValidationException $exception) {
            $this->showValidation($exception);

            return;
        }

        Notification::make()->title(__('pkl.bimbingan.final_approved'))->success()->send();
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
