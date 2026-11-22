<?php

namespace App\Actions\Industri;

use App\Actions\Action;
use App\Models\AcademicPeriod;
use App\Models\Company;
use App\Models\FormDefinition;
use App\Models\FormSubmission;
use App\Models\FormSubmissionSiswa;
use App\Models\MagicLink;
use App\Models\PengajuanSiswa;
use App\Models\User;
use App\Notifications\IndustryLinkNotification;
use App\Services\MagicLinkService;
use App\Support\IndustryWindow;
use App\Support\SignerGuard;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * Terbitkan tautan penilaian industri per (form definition, perusahaan, periode)
 * (F10, Q-B). Submission + pivot siswa dibuat di transaksi yang sama; tautan
 * selalu mencabut tautan aktif sebelumnya lewat MagicLinkService.
 */
final class GenerateIndustryLink extends Action
{
    /**
     * @return array{link: MagicLink, token: string, url: string}
     */
    public function handle(mixed ...$arguments): array
    {
        [$company, $period, $definition, $actor] = $arguments;

        if (! $company instanceof Company || ! $period instanceof AcademicPeriod
            || ! $definition instanceof FormDefinition || ! $actor instanceof User) {
            throw new RuntimeException('Perusahaan, periode, form, dan aktor wajib diisi.');
        }

        Gate::forUser($actor)->authorize('issueIndustryLink', [FormSubmission::class, $company, $period]);

        $this->assertDefinition($definition, $period);

        if (! IndustryWindow::isOpen($company, $period, (int) $definition->jurusan_id)) {
            throw ValidationException::withMessages(['window' => __('pkl.industri.window_closed')]);
        }

        if (! SignerGuard::canReceiveOtp(
            $company->pembimbing_industri_nama,
            $company->pembimbing_industri_email,
            $company->pembimbing_industri_email_verified_at,
        )) {
            throw ValidationException::withMessages(['signer' => __('pkl.industri.signer_unverified')]);
        }

        $studentIds = $this->studentIds($company, $period, (int) $definition->jurusan_id);

        if ($studentIds === []) {
            throw ValidationException::withMessages(['students' => __('pkl.industri.students_missing')]);
        }

        $issued = $this->transaction(function () use ($company, $period, $definition, $actor, $studentIds): array {
            Company::query()->whereKey($company->getKey())->lockForUpdate()->firstOrFail();

            $submission = FormSubmission::query()
                ->where('form_definition_id', $definition->getKey())
                ->where('company_id', $company->getKey())
                ->where('academic_period_id', $period->getKey())
                ->lockForUpdate()
                ->first();

            if ($submission?->is_submitted) {
                throw ValidationException::withMessages(['company' => __('pkl.industri.submission_final')]);
            }

            $submission ??= FormSubmission::query()->create([
                'form_definition_id' => $definition->getKey(),
                'form_type' => FormDefinition::TYPE_INDUSTRI,
                'company_id' => $company->getKey(),
                'academic_period_id' => $period->getKey(),
                'jawaban' => ['items' => []],
            ]);

            FormSubmissionSiswa::query()
                ->where('form_submission_id', $submission->getKey())
                ->delete();

            foreach ($studentIds as $studentId) {
                FormSubmissionSiswa::query()->create([
                    'form_submission_id' => $submission->getKey(),
                    'form_type' => FormDefinition::TYPE_INDUSTRI,
                    'pengajuan_siswa_id' => $studentId,
                ]);
            }

            $issued = app(MagicLinkService::class)->issue(
                MagicLinkService::TYPE_INDUSTRY_ASSESSMENT,
                (int) $submission->getKey(),
                null,
                $actor,
            );

            $this->audit($submission, 'tautan_industri_diterbitkan', null, 'aktif', [
                'magic_link_id' => $issued['link']->getKey(),
                'form_definition_id' => $definition->getKey(),
                'company_id' => $company->getKey(),
                'jumlah_siswa' => count($studentIds),
            ], 'Tautan penilaian industri diterbitkan');

            return $issued;
        });

        $url = route('eksternal.industri', ['token' => $issued['token']]);

        Notification::route('mail', trim((string) $company->pembimbing_industri_email))
            ->notify(new IndustryLinkNotification($url, (string) $company->nama));

        return $issued + ['url' => $url];
    }

    private function assertDefinition(FormDefinition $definition, AcademicPeriod $period): void
    {
        if ($definition->form_type !== FormDefinition::TYPE_INDUSTRI
            || ! $definition->is_published
            || (int) $definition->academic_period_id !== (int) $period->getKey()) {
            throw ValidationException::withMessages(['form' => __('pkl.industri.form_missing')]);
        }
    }

    /** @return array<int, int> */
    private function studentIds(Company $company, AcademicPeriod $period, int $jurusanId): array
    {
        return PengajuanSiswa::query()
            ->where('company_id', $company->getKey())
            ->where('academic_period_id', $period->getKey())
            ->where('status', 'sedang_pkl')
            ->whereHas('siswa', fn (Builder $student) => $student->where('jurusan_id', $jurusanId))
            ->orderBy('id')
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();
    }
}
