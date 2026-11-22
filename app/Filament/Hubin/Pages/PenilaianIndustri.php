<?php

namespace App\Filament\Hubin\Pages;

use App\Actions\Industri\GenerateIndustryLink;
use App\Actions\Industri\ReissueIndustryLink;
use App\Actions\Industri\RevokeIndustryLink;
use App\Enums\Role;
use App\Models\FormDefinition;
use App\Models\FormSubmission;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/**
 * Override Hubin untuk tautan penilaian industri (F10/BR-F10-3): cabut atau
 * terbitkan ulang dengan alasan wajib yang masuk audit.
 */
class PenilaianIndustri extends Page
{
    protected string $view = 'filament.hubin.pages.penilaian-industri';

    protected static ?string $navigationLabel = 'Penilaian industri';

    protected static ?string $slug = 'penilaian-industri';

    protected static ?string $title = 'Penilaian Industri';

    protected static ?int $navigationSort = 5;

    public ?int $actionId = null;

    public string $reason = '';

    public ?string $issuedUrl = null;

    public static function canAccess(): bool
    {
        return session('active_role') === Role::Hubin->value;
    }

    /** @return Collection<int, FormSubmission> */
    public function submissions()
    {
        return FormSubmission::query()
            ->with(['company', 'academicPeriod', 'definition', 'students.pengajuanSiswa.siswa'])
            ->where('form_type', FormDefinition::TYPE_INDUSTRI)
            ->orderByDesc('id')
            ->get();
    }

    public function startRevoke(int $submissionId): void
    {
        $this->actionId = $submissionId;
        $this->reason = '';
    }

    /** Terbitkan tautan baru saat tautan lama kedaluwarsa (override Hubin). */
    public function issue(int $submissionId): void
    {
        $submission = FormSubmission::query()->with(['company', 'academicPeriod', 'definition'])->findOrFail($submissionId);

        try {
            $result = app(GenerateIndustryLink::class)->handle(
                $submission->company,
                $submission->academicPeriod,
                $submission->definition,
                auth()->user(),
            );
        } catch (ValidationException $exception) {
            $this->showValidation($exception);

            return;
        }

        $this->issuedUrl = $result['url'];
        Notification::make()->title(__('pkl.industri.link_issued'))->success()->send();
    }

    public function revoke(): void
    {
        $this->validate([
            'actionId' => ['required', 'integer', 'exists:form_submissions,id'],
            'reason' => ['required', 'string', 'min:10', 'max:500'],
        ]);

        try {
            app(RevokeIndustryLink::class)->handle(
                FormSubmission::query()->findOrFail($this->actionId),
                auth()->user(),
                $this->reason,
            );
        } catch (ValidationException $exception) {
            $this->showValidation($exception);

            return;
        }

        $this->reset('actionId', 'reason', 'issuedUrl');
        Notification::make()->title(__('pkl.industri.link_revoked'))->success()->send();
    }

    public function reissue(): void
    {
        $this->validate([
            'actionId' => ['required', 'integer', 'exists:form_submissions,id'],
            'reason' => ['required', 'string', 'min:10', 'max:500'],
        ]);

        try {
            $result = app(ReissueIndustryLink::class)->handle(
                FormSubmission::query()->findOrFail($this->actionId),
                auth()->user(),
                $this->reason,
            );
        } catch (ValidationException $exception) {
            $this->showValidation($exception);

            return;
        }

        $this->issuedUrl = $result['url'];
        $this->reset('actionId', 'reason');
        Notification::make()->title(__('pkl.industri.link_reissued'))->success()->send();
    }

    /** @return array<int, array<string, mixed>> */
    public function stateLabels(): array
    {
        return [
            'none' => __('pkl.industri.state_none'),
            'active' => __('pkl.industri.state_active'),
            'expired' => __('pkl.industri.state_expired'),
            'final' => __('pkl.industri.state_final'),
        ];
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
