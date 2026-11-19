<?php

namespace App\Filament\App\Pages;

use App\Actions\Visitasi\RequestVisitasi;
use App\Actions\Visitasi\UploadDokumentasi;
use App\Enums\Role;
use App\Models\Company;
use App\Models\SuratTugasVisitasi;
use App\Models\User;
use App\Services\PlottingService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\WithFileUploads;

class Visitasi extends Page
{
    use WithFileUploads;

    protected string $view = 'filament.app.pages.visitasi';

    protected static ?string $navigationLabel = 'Visitasi';

    protected static ?string $title = 'Visitasi PKL';

    protected static ?int $navigationSort = 3;

    public ?int $guruId = null;

    /** @var array<int, int|string> */
    public array $companyIds = [];

    public bool $isGeneral = false;

    public ?int $selectedVisitasiId = null;

    public ?int $uploadCompanyId = null;

    public ?string $visitDate = null;

    public mixed $photo = null;

    public ?string $note = null;

    public static function canAccess(): bool
    {
        return in_array(session('active_role'), [Role::Kaprog->value, Role::GuruPembimbing->value], true);
    }

    public function teachers(): array
    {
        return app(PlottingService::class)->guruCandidatesForKaprog(auth()->user())
            ->mapWithKeys(fn (User $guru): array => [$guru->id => $guru->name])
            ->all();
    }

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
        $user = auth()->user();
        Gate::forUser($user)->authorize('viewAny', SuratTugasVisitasi::class);

        $query = SuratTugasVisitasi::query()->with(['guru', 'companies.company', 'dokumentasi.company']);

        $query = match (session('active_role')) {
            Role::Kaprog->value => $query->where('kaprog_id', $user->getKey()),
            Role::GuruPembimbing->value => $query->where('guru_id', $user->getKey()),
            default => $query->whereRaw('1 = 0'),
        };

        return $query->latest('created_at')->limit(100)->get()->all();
    }

    public function submitRequest(): void
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
        Notification::make()->title('Pengajuan visitasi dikirim ke Hubin.')->success()->send();
    }

    public function selectUpload(int $visitasiId): void
    {
        $visitasi = SuratTugasVisitasi::query()->findOrFail($visitasiId);
        Gate::forUser(auth()->user())->authorize('view', $visitasi);
        $this->selectedVisitasiId = $visitasiId;
        $this->visitDate = now()->timezone('Asia/Jakarta')->toDateString();
        $this->uploadCompanyId = null;
        $this->reset('photo', 'note');
    }

    public function uploadDocumentation(): void
    {
        $this->validate([
            'selectedVisitasiId' => ['required', 'integer', 'exists:surat_tugas_visitasi,id'],
            'uploadCompanyId' => ['required', 'integer', 'exists:companies,id'],
            'visitDate' => ['required', 'date'],
            'photo' => ['required', 'file'],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            app(UploadDokumentasi::class)->handle(
                SuratTugasVisitasi::query()->findOrFail($this->selectedVisitasiId),
                $this->uploadCompanyId,
                $this->visitDate,
                $this->photo,
                auth()->user(),
                $this->note,
            );
        } catch (ValidationException $exception) {
            $this->showValidation($exception);

            return;
        }

        $this->reset('selectedVisitasiId', 'uploadCompanyId', 'visitDate', 'photo', 'note');
        Notification::make()->title('Dokumentasi visitasi berhasil diunggah.')->success()->send();
    }

    /** @return array<int, string> */
    public function uploadCompanies(): array
    {
        if ($this->selectedVisitasiId === null) {
            return [];
        }

        $visitasi = SuratTugasVisitasi::query()->find($this->selectedVisitasiId);

        if ($visitasi === null) {
            return [];
        }

        if ($visitasi->is_general) {
            return app(PlottingService::class)->companiesForGuru(auth()->user())
                ->mapWithKeys(fn (Company $company): array => [$company->id => $company->nama])
                ->all();
        }

        return $visitasi->companies()->with('company')->get()
            ->mapWithKeys(fn ($row): array => [$row->company_id => $row->company?->nama ?? 'Perusahaan'])
            ->all();
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
