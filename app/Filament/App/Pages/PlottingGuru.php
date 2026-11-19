<?php

namespace App\Filament\App\Pages;

use App\Actions\Plotting\AssignGuru;
use App\Actions\Plotting\IssueSuratTugasPembimbing;
use App\Exceptions\DocumentIssuanceHeld;
use App\Models\AcademicPeriod;
use App\Models\Company;
use App\Models\GuruPembimbingPlotting;
use App\Models\User;
use App\Services\PlottingService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class PlottingGuru extends Page
{
    protected string $view = 'filament.app.pages.plotting-guru';

    protected static ?string $navigationLabel = 'Plotting guru';

    protected static ?string $title = 'Plotting Guru Pembimbing';

    protected static ?int $navigationSort = 2;

    public ?int $periodId = null;

    public ?int $companyId = null;

    public ?int $guruId = null;

    public static function canAccess(): bool
    {
        return session('active_role') === 'kaprog';
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

    /** @return Collection<int, Company> */
    public function companies()
    {
        $period = $this->periodId === null ? null : AcademicPeriod::query()->find($this->periodId);

        return $period === null ? collect() : app(PlottingService::class)->eligibleCompanies($period, auth()->user());
    }

    /** @return Collection<int, User> */
    public function teachers()
    {
        $period = $this->periodId === null ? null : AcademicPeriod::query()->find($this->periodId);

        return $period === null ? collect() : app(PlottingService::class)->guruCandidates($period);
    }

    public function savePlotting(): void
    {
        $this->validate([
            'periodId' => ['required', 'integer', 'exists:academic_periods,id'],
            'companyId' => ['required', 'integer', 'exists:companies,id'],
            'guruId' => ['required', 'integer', 'exists:users,id'],
        ]);

        try {
            app(AssignGuru::class)->handle(
                Company::query()->findOrFail($this->companyId),
                AcademicPeriod::query()->findOrFail($this->periodId),
                User::query()->findOrFail($this->guruId),
                auth()->user(),
            );
        } catch (ValidationException $exception) {
            foreach ($exception->errors() as $field => $messages) {
                $this->addError($field, $messages[0]);
            }

            return;
        }

        Notification::make()->title('Plotting guru berhasil disimpan.')->success()->send();
        $this->reset('companyId', 'guruId');
    }

    public function issueLetter(int $plottingId): void
    {
        $plotting = GuruPembimbingPlotting::query()->findOrFail($plottingId);

        try {
            app(IssueSuratTugasPembimbing::class)->handle($plotting, auth()->user());
        } catch (DocumentIssuanceHeld $exception) {
            Notification::make()->title($exception->getMessage())->warning()->send();

            return;
        }

        Notification::make()->title('Surat tugas pembimbing diterbitkan.')->success()->send();
    }
}
