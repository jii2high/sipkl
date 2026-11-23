<?php

namespace App\Jobs;

use App\Enums\PengajuanStatus;
use App\Models\Company;
use App\Models\FormDefinition;
use App\Models\FormSubmission;
use App\Models\GuruPembimbingPlotting;
use App\Models\PengajuanSiswa;
use App\Notifications\Notification as AppNotification;
use App\Services\NotificationService;
use App\Support\JakartaTime;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

/**
 * Pengingat H-7 penilaian industri (BR-F10-4, KP-F10-4). Dikirim sekali per
 * tanggal Jakarta per perusahaan, hanya bila tautan belum dibuat. Acuan tanggal
 * adalah `tanggal_selesai_pkl` siswa aktif paling awal.
 */
class IndustryAssessmentReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 3;

    /** @return array<int, int> */
    public function backoff(): array
    {
        return [30, 120, 600];
    }

    public function handle(NotificationService $notifications): void
    {
        $today = JakartaTime::today();
        $days = (int) config('pkl.industry_assessment.reminder_days_before_end');
        $reminderDate = $today->addDays($days);

        $groups = PengajuanSiswa::query()
            ->with(['siswa', 'company'])
            ->where('status', PengajuanStatus::SedangPkl->value)
            ->whereNotNull('tanggal_selesai_pkl')
            ->whereHas('siswa')
            ->get()
            ->groupBy(fn (PengajuanSiswa $row): string => $row->company_id.'|'.$row->academic_period_id.'|'.$row->siswa?->jurusan_id);

        foreach ($groups as $key => $group) {
            [$companyId, $periodId, $jurusanId] = array_pad(explode('|', (string) $key), 3, null);

            if ($companyId === null || $periodId === null || $jurusanId === null || $jurusanId === '') {
                continue;
            }

            $earliestEnd = $group->min(fn (PengajuanSiswa $row) => $row->tanggal_selesai_pkl);

            if ($earliestEnd === null || ! $earliestEnd->isSameDay($reminderDate)) {
                continue;
            }

            $company = $group->first()?->company;

            if ($company === null || $company->last_industry_reminder_at?->toDateString() === $today->toDateString()) {
                continue;
            }

            $definition = FormDefinition::query()
                ->where('jurusan_id', (int) $jurusanId)
                ->where('academic_period_id', (int) $periodId)
                ->where('form_type', FormDefinition::TYPE_INDUSTRI)
                ->where('is_published', true)
                ->orderByDesc('id')
                ->first();

            if ($definition === null || $this->alreadyLinked($definition, $company, (int) $periodId)) {
                continue;
            }

            $guru = GuruPembimbingPlotting::query()
                ->where('company_id', $company->getKey())
                ->where('academic_period_id', (int) $periodId)
                ->first()?->guru;

            if ($guru === null) {
                continue;
            }

            $notifications->send(
                $guru,
                new AppNotification(
                    'Tautan penilaian industri belum dibuat',
                    'Tautan penilaian industri untuk '.$company->nama.' belum dibuat. PKL siswa selesai '.$days.' hari lagi.',
                    url('/guru/penilaian-industri'),
                ),
                ['related' => $company],
            );

            Company::query()
                ->whereKey($company->getKey())
                ->update(['last_industry_reminder_at' => $today->toDateString()]);
        }
    }

    private function alreadyLinked(FormDefinition $definition, Company $company, int $periodId): bool
    {
        return FormSubmission::query()
            ->where('form_definition_id', $definition->getKey())
            ->where('company_id', $company->getKey())
            ->where('academic_period_id', $periodId)
            ->exists();
    }
}
