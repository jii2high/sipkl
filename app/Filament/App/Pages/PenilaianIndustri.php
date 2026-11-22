<?php

namespace App\Filament\App\Pages;

use App\Actions\Industri\GenerateIndustryLink;
use App\Actions\Industri\ReissueIndustryLink;
use App\Actions\Industri\RevokeIndustryLink;
use App\Enums\Role;
use App\Models\AcademicPeriod;
use App\Models\Company;
use App\Models\FormDefinition;
use App\Models\FormSubmission;
use App\Models\PengajuanSiswa;
use App\Services\PlottingService;
use App\Support\IndustryWindow;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Validation\ValidationException;

/**
 * Penilaian industri per perusahaan untuk guru pembimbing (F10): satu tautan per
 * form definition (jurusan) untuk semua siswa bimbingan aktif di perusahaan itu.
 */
class PenilaianIndustri extends Page
{
    protected string $view = 'filament.app.pages.penilaian-industri';

    protected static ?string $navigationLabel = 'Penilaian industri';

    protected static ?string $slug = 'penilaian-industri';

    protected static ?string $title = 'Penilaian Industri';

    protected static ?int $navigationSort = 8;

    public ?int $periodId = null;

    public ?int $actionId = null;

    public string $reason = '';

    public ?string $issuedUrl = null;

    public static function canAccess(): bool
    {
        return session('active_role') === Role::GuruPembimbing->value;
    }

    public function mount(): void
    {
        $this->periodId = AcademicPeriod::query()
            ->where('is_active', true)
            ->orderByDesc('tanggal_mulai')
            ->value('id');
    }

    /** @return array<int, string> */
    public function periods(): array
    {
        return AcademicPeriod::query()
            ->orderByDesc('tanggal_mulai')
            ->get()
            ->mapWithKeys(fn (AcademicPeriod $period): array => [
                $period->getKey() => $period->tahun_ajaran.' · batch '.$period->batch,
            ])
            ->all();
    }

    /**
     * @return array<int, array{company: Company, definition: FormDefinition, students: mixed, submission: ?FormSubmission, window: bool}>
     */
    public function rows(): array
    {
        $period = $this->periodId === null ? null : AcademicPeriod::query()->find($this->periodId);

        if ($period === null) {
            return [];
        }

        $companies = app(PlottingService::class)->companiesForGuru(auth()->user(), (int) $period->getKey());

        if ($companies->isEmpty()) {
            return [];
        }

        $definitions = FormDefinition::query()
            ->where('academic_period_id', $period->getKey())
            ->where('form_type', FormDefinition::TYPE_INDUSTRI)
            ->where('is_published', true)
            ->orderByDesc('id')
            ->get()
            ->unique('jurusan_id')
            ->keyBy('jurusan_id');

        $students = PengajuanSiswa::query()
            ->with('siswa')
            ->where('academic_period_id', $period->getKey())
            ->where('status', 'sedang_pkl')
            ->whereIn('company_id', $companies->pluck('id'))
            ->orderBy('id')
            ->get()
            ->groupBy(fn (PengajuanSiswa $row): string => $row->company_id.'|'.$row->siswa?->jurusan_id);

        $submissions = FormSubmission::query()
            ->where('form_type', FormDefinition::TYPE_INDUSTRI)
            ->where('academic_period_id', $period->getKey())
            ->whereIn('company_id', $companies->pluck('id'))
            ->get()
            ->keyBy(fn (FormSubmission $row): string => $row->form_definition_id.'|'.$row->company_id);

        $rows = [];

        foreach ($companies as $company) {
            foreach ($students as $key => $group) {
                [$companyId, $jurusanId] = array_pad(explode('|', (string) $key), 2, null);

                if ((int) $companyId !== (int) $company->getKey()) {
                    continue;
                }

                $definition = $definitions->get((int) $jurusanId);

                if ($definition === null) {
                    continue;
                }

                $rows[] = [
                    'company' => $company,
                    'definition' => $definition,
                    'students' => $group,
                    'submission' => $submissions->get($definition->getKey().'|'.$company->getKey()),
                    'window' => IndustryWindow::isOpen($company, $period, (int) $jurusanId),
                ];
            }
        }

        return $rows;
    }

    public function generate(int $companyId, int $definitionId): void
    {
        $definition = FormDefinition::query()->findOrFail($definitionId);
        $period = AcademicPeriod::query()->findOrFail($definition->academic_period_id);

        try {
            $result = app(GenerateIndustryLink::class)->handle(
                Company::query()->findOrFail($companyId),
                $period,
                $definition,
                auth()->user(),
            );
        } catch (ValidationException $exception) {
            $this->showValidation($exception);

            return;
        }

        $this->issuedUrl = $result['url'];
        Notification::make()
            ->title(__('pkl.industri.link_issued'))
            ->body('Tautan juga dikirim ke email pembimbing industri.')
            ->success()
            ->persistent()
            ->send();
    }

    public function startRevoke(int $submissionId): void
    {
        $this->actionId = $submissionId;
        $this->reason = '';
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
