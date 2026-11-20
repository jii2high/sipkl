<?php

namespace App\Filament\Hubin\Pages;

use App\Actions\Visitasi\ApproveVisitasi;
use App\Actions\Visitasi\IssueSuratTugasVisitasi;
use App\Actions\Visitasi\RejectVisitasi;
use App\Actions\Visitasi\RequestVisitasi;
use App\Enums\Role;
use App\Models\Company;
use App\Models\SuratTugasVisitasi;
use App\Models\User;
use App\Services\PlottingService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class KelolaVisitasi extends Page
{
    protected string $view = 'filament.hubin.pages.kelola-visitasi';

    protected static ?string $navigationLabel = 'Visitasi';

    protected static ?string $title = 'Pengelolaan Visitasi';

    protected static ?int $navigationSort = 3;

    public ?int $guruId = null;

    /** @var array<int, int|string> */
    public array $companyIds = [];

    public bool $isGeneral = false;

    public ?int $rejectingId = null;

    public ?string $rejectionReason = null;

    public static function canAccess(): bool
    {
        return session('active_role') === Role::Hubin->value;
    }

    /** @return array<int, string> */
    public function teachers(): array
    {
        return User::query()
            ->where('is_active', true)
            ->whereHas('roles', fn ($query) => $query->where('role', Role::GuruPembimbing->value))
            ->whereHas('plottings')
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    /** @return array<int, string> */
    public function companies(): array
    {
        if ($this->guruId === null) {
            return [];
        }

        return app(PlottingService::class)->companiesForGuru(User::query()->findOrFail($this->guruId))
            ->mapWithKeys(fn (Company $company): array => [$company->id => $company->nama])
            ->all();
    }

    /** @return array<int, SuratTugasVisitasi> */
    public function visitations(): array
    {
        Gate::forUser(auth()->user())->authorize('viewAny', SuratTugasVisitasi::class);

        return SuratTugasVisitasi::query()
            ->with(['guru', 'kaprog', 'companies.company'])
            ->latest('created_at')
            ->limit(100)
            ->get()
            ->all();
    }

    public function createManual(): void
    {
        $this->validate([
            'guruId' => ['required', 'integer', 'exists:users,id'],
            'companyIds' => ['array'],
            'companyIds.*' => ['integer', 'exists:companies,id'],
        ]);

        try {
            app(RequestVisitasi::class)->handle(
                User::query()->findOrFail($this->guruId),
                array_map('intval', $this->companyIds),
                $this->isGeneral,
                auth()->user(),
            );
        } catch (ValidationException $exception) {
            $this->showValidation($exception);

            return;
        }

        $this->reset('guruId', 'companyIds', 'isGeneral');
        Notification::make()->title('Surat visitasi manual dibuat untuk persetujuan.')->success()->send();
    }

    public function approve(int $id): void
    {
        $visitasi = SuratTugasVisitasi::query()->findOrFail($id);

        try {
            app(ApproveVisitasi::class)->handle($visitasi, auth()->user());
        } catch (ValidationException $exception) {
            $this->showValidation($exception);

            return;
        }

        Notification::make()->title('Visitasi disetujui.')->success()->send();
    }

    public function issueLetter(int $id): void
    {
        try {
            app(IssueSuratTugasVisitasi::class)->handle(
                SuratTugasVisitasi::query()->findOrFail($id),
                auth()->user(),
            );
        } catch (\Throwable $exception) {
            Notification::make()->title($exception->getMessage())->danger()->send();

            return;
        }

        Notification::make()->title('Nomor surat visitasi berhasil diterbitkan.')->success()->send();
    }

    public function startReject(int $id): void
    {
        $this->rejectingId = $id;
        $this->rejectionReason = null;
    }

    public function reject(): void
    {
        $this->validate(['rejectionReason' => ['required', 'string']]);

        try {
            app(RejectVisitasi::class)->handle(
                SuratTugasVisitasi::query()->findOrFail($this->rejectingId),
                auth()->user(),
                $this->rejectionReason,
            );
        } catch (ValidationException $exception) {
            $this->showValidation($exception);

            return;
        }

        $this->reset('rejectingId', 'rejectionReason');
        Notification::make()->title('Pengajuan visitasi ditolak.')->success()->send();
    }

    private function showValidation(ValidationException $exception): void
    {
        foreach ($exception->errors() as $field => $messages) {
            $this->addError($field, $messages[0]);
        }

        Notification::make()
            ->title('Data visitasi belum valid')
            ->body($exception->errors()[array_key_first($exception->errors())][0] ?? '')
            ->danger()
            ->send();
    }
}
