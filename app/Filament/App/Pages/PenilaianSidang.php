<?php

namespace App\Filament\App\Pages;

use App\Actions\Sidang\ApproveUnlock;
use App\Actions\Sidang\SetKelulusan;
use App\Enums\Role;
use App\Models\FormDefinition;
use App\Models\FormSubmission;
use App\Models\NilaiSidangRevision;
use App\Models\SidangResultsSummary;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/**
 * Tindak lanjut nilai sidang oleh Kaprog (F9, Q-I): menyetujui permintaan revisi
 * dari penguji dan menetapkan status kelulusan secara manual.
 */
class PenilaianSidang extends Page
{
    protected string $view = 'filament.app.pages.penilaian-sidang';

    protected static ?string $navigationLabel = 'Nilai sidang';

    protected static ?string $title = 'Nilai Sidang';

    protected static ?int $navigationSort = 8;

    public ?int $kelulusanId = null;

    public string $kelulusanStatus = '';

    public string $kelulusanCatatan = '';

    public ?string $waUrl = null;

    public static function canAccess(): bool
    {
        return session('active_role') === Role::Kaprog->value;
    }

    /** @return Collection<int, NilaiSidangRevision> */
    public function revisions()
    {
        return NilaiSidangRevision::query()
            ->with(['submission.pengajuanSiswa.siswa', 'submission.jadwalSidang.externalExaminer'])
            ->where('status', 'menunggu_persetujuan')
            ->whereHas('submission.pengajuanSiswa.siswa', fn ($student) => $student
                ->where('jurusan_id', auth()->user()->roleScopeId(Role::Kaprog)))
            ->orderBy('id')
            ->get();
    }

    /** @return Collection<int, FormSubmission> */
    public function submissions()
    {
        return FormSubmission::query()
            ->with(['pengajuanSiswa.siswa', 'jadwalSidang.externalExaminer'])
            ->where('form_type', FormDefinition::TYPE_SIDANG)
            ->where('is_submitted', true)
            ->whereHas('pengajuanSiswa.siswa', fn ($student) => $student
                ->where('jurusan_id', auth()->user()->roleScopeId(Role::Kaprog)))
            ->orderByDesc('submitted_at')
            ->get();
    }

    public function summary(FormSubmission $submission): ?SidangResultsSummary
    {
        return SidangResultsSummary::query()
            ->where('form_submission_id', $submission->getKey())
            ->first();
    }

    public function approve(int $revisionId): void
    {
        try {
            $result = app(ApproveUnlock::class)->handle(
                NilaiSidangRevision::query()->findOrFail($revisionId),
                auth()->user(),
            );
        } catch (ValidationException $exception) {
            $this->showValidation($exception);

            return;
        }

        $this->waUrl = $result['wa_url'] ?: null;
        Notification::make()
            ->title(__('pkl.sidang.unlock_approved'))
            ->body('PIN: '.$result['pin'])
            ->success()
            ->persistent()
            ->send();
    }

    public function openKelulusan(int $submissionId): void
    {
        $submission = FormSubmission::query()->findOrFail($submissionId);
        $summary = $this->summary($submission);

        $this->kelulusanId = $submission->getKey();
        $this->kelulusanStatus = (string) ($summary?->status_kelulusan ?? '');
        $this->kelulusanCatatan = (string) ($summary?->catatan_kelulusan ?? '');
    }

    public function saveKelulusan(): void
    {
        $this->validate([
            'kelulusanId' => ['required', 'integer', 'exists:form_submissions,id'],
            'kelulusanStatus' => ['required', 'in:Lulus,Tidak Lulus'],
            'kelulusanCatatan' => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            app(SetKelulusan::class)->handle(
                FormSubmission::query()->findOrFail($this->kelulusanId),
                $this->kelulusanStatus,
                $this->kelulusanCatatan,
                auth()->user(),
            );
        } catch (ValidationException $exception) {
            $this->showValidation($exception);

            return;
        }

        $this->reset('kelulusanId', 'kelulusanStatus', 'kelulusanCatatan');
        Notification::make()->title(__('pkl.sidang.kelulusan_saved'))->success()->send();
    }

    private function showValidation(ValidationException $exception): void
    {
        foreach ($exception->errors() as $field => $messages) {
            $this->addError($field, $messages[0]);
        }
    }
}
