<?php

namespace App\Filament\App\Pages;

use App\Actions\Pengajuan\ReopenPengajuan;
use App\Actions\Pengajuan\RequestWithdrawal;
use App\Actions\Pengajuan\SaveDraft;
use App\Actions\Pengajuan\SubmitPengajuan;
use App\Actions\Pengajuan\SubmitSuratBalasan;
use App\Enums\PengajuanStatus;
use App\Models\AcademicPeriod;
use App\Models\Company;
use App\Models\PengajuanSiswa;
use App\Services\CompanyResolver;
use App\Services\FileStorageService;
use App\Support\MaskedContact;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;
use Livewire\WithFileUploads;
use Spatie\Activitylog\Models\Activity;

/**
 * Layar pengajuan siswa (F3 alur 1-2): pilih/ketik perusahaan, simpan draf, kirim,
 * lihat status dan riwayat penolakan. Semua perubahan status lewat Action.
 */
class PengajuanSaya extends Page
{
    use WithFileUploads;

    protected string $view = 'filament.app.pages.pengajuan-saya';

    protected static ?string $navigationLabel = 'Pengajuan PKL';

    protected static ?string $title = 'Pengajuan PKL';

    protected static ?int $navigationSort = 1;

    public ?int $pengajuanId = null;

    public ?int $periodId = null;

    public ?int $companyId = null;

    public bool $newCompany = false;

    /** @var array<string, string|null> */
    public array $company = [
        'nama' => null,
        'alamat' => null,
        'email' => null,
        'website' => null,
        'telp_fax' => null,
        'cp_name' => null,
        'cp_phone' => null,
    ];

    /** @var array<int, string> */
    public array $similarityWarnings = [];

    public bool $similarityConfirmed = false;

    public mixed $suratBalasan = null;

    public ?string $suratBalasanHasil = null;

    public ?string $withdrawReason = null;

    public static function canAccess(): bool
    {
        return session('active_role') === 'siswa';
    }

    public function mount(): void
    {
        $this->periodId = AcademicPeriod::query()
            ->where('is_active', true)
            ->orderByDesc('tanggal_mulai')
            ->value('id');

        $pengajuan = $this->pengajuan();

        if ($pengajuan !== null) {
            $this->pengajuanId = (int) $pengajuan->getKey();
            $this->companyId = (int) $pengajuan->company_id;
        }
    }

    public function getSubheading(): ?string
    {
        $period = $this->period();

        return $period === null
            ? __('pkl.pengajuan.no_active_period')
            : 'Periode '.$period->tahun_ajaran.' batch '.$period->batch;
    }

    public function pengajuan(): ?PengajuanSiswa
    {
        if ($this->pengajuanId !== null) {
            return PengajuanSiswa::query()
                ->with(['company', 'academicPeriod'])
                ->where('siswa_id', auth()->id())
                ->find($this->pengajuanId);
        }

        if ($this->periodId === null) {
            return null;
        }

        return PengajuanSiswa::query()
            ->with(['company', 'academicPeriod'])
            ->where('siswa_id', auth()->id())
            ->where('academic_period_id', $this->periodId)
            ->latest('id')
            ->first();
    }

    public function status(): ?PengajuanStatus
    {
        $status = $this->pengajuan()?->status;

        return $status === null ? null : PengajuanStatus::tryFrom($status);
    }

    public function isDraft(): bool
    {
        return $this->status() === PengajuanStatus::Draft;
    }

    public function canReopen(): bool
    {
        return in_array($this->status(), [
            PengajuanStatus::DitolakKaprog,
            PengajuanStatus::DitolakWalas,
        ], true);
    }

    public function isWaiting(): bool
    {
        $status = $this->status();

        return $status !== null
            && ! in_array($status, [
                PengajuanStatus::Draft,
                PengajuanStatus::DitolakKaprog,
                PengajuanStatus::DitolakWalas,
                PengajuanStatus::Withdrawn,
            ], true);
    }

    public function isWithdrawn(): bool
    {
        return $this->status() === PengajuanStatus::Withdrawn;
    }

    public function canUploadSuratBalasan(): bool
    {
        return $this->status() === PengajuanStatus::SuratPengantarTerbit;
    }

    public function isSedangPkl(): bool
    {
        return $this->status() === PengajuanStatus::SedangPkl;
    }

    public function hasPendingWithdrawal(): bool
    {
        return $this->pengajuan()?->withdraw_requested_at !== null;
    }

    public function uploadSuratBalasan(): void
    {
        $pengajuan = $this->pengajuan();

        if ($pengajuan === null || ! $this->canUploadSuratBalasan()) {
            return;
        }

        try {
            $this->validate([
                'suratBalasan' => [
                    'required',
                    'file',
                    'mimes:'.implode(',', (array) config('pkl.surat_balasan.extensions')),
                    'max:'.((int) config('pkl.surat_balasan.max_mb') * 1024),
                ],
                'suratBalasanHasil' => ['required', 'in:diterima,ditolak'],
            ]);
        } catch (ValidationException $exception) {
            $this->reportValidation($exception);

            return;
        }

        try {
            $path = app(FileStorageService::class)->store(
                $this->suratBalasan,
                'surat_balasan',
                'surat-balasan',
            );

            app(SubmitSuratBalasan::class)->handle($pengajuan, auth()->user(), $path, $this->suratBalasanHasil);
        } catch (ValidationException $exception) {
            if (isset($path)) {
                Storage::disk('local')->delete($path);
            }

            $this->reportValidation($exception);

            return;
        }

        $this->reset('suratBalasan', 'suratBalasanHasil');

        Notification::make()->title(__('pkl.pengajuan.surat_balasan_uploaded'))->success()->send();
    }

    public function requestWithdrawal(): void
    {
        $pengajuan = $this->pengajuan();

        if ($pengajuan === null || ! $this->isSedangPkl() || $this->hasPendingWithdrawal()) {
            return;
        }

        try {
            app(RequestWithdrawal::class)->handle($pengajuan, auth()->user(), $this->withdrawReason);
        } catch (ValidationException $exception) {
            $this->reportValidation($exception);

            return;
        }

        $this->reset('withdrawReason');

        Notification::make()->title(__('pkl.pengajuan.withdraw_requested'))->success()->send();
    }

    public function period(): ?AcademicPeriod
    {
        return $this->periodId === null
            ? null
            : AcademicPeriod::query()->find($this->periodId);
    }

    public function selectedCompany(): ?Company
    {
        return $this->companyId === null
            ? null
            : Company::query()->find($this->companyId);
    }

    public function maskedPhone(): ?string
    {
        $company = $this->selectedCompany();

        return $company === null ? null : MaskedContact::phone($company->cp_phone);
    }

    public function contactUnlocked(): bool
    {
        return $this->pengajuan()?->contactUnlocked() ?? false;
    }

    /** @return array<int, Activity> */
    public function history(): array
    {
        if ($this->pengajuanId === null) {
            return [];
        }

        return Activity::query()
            ->where('subject_type', PengajuanSiswa::class)
            ->where('subject_id', $this->pengajuanId)
            ->latest('id')
            ->limit(20)
            ->get()
            ->all();
    }

    public function updatedNewCompany(bool $value): void
    {
        if ($value) {
            $this->companyId = null;
            $this->similarityWarnings = [];
        }
    }

    public function clearCompany(): void
    {
        $this->companyId = null;
        $this->similarityWarnings = [];
        $this->similarityConfirmed = false;
    }

    #[On('company-selected')]
    public function companySelected(int $id, string $nama): void
    {
        $this->companyId = $id;
        $this->newCompany = false;
        $this->similarityWarnings = [];
        $this->similarityConfirmed = false;
    }

    public function saveDraft(bool $notify = true): bool
    {
        if (! $this->isDraft() && $this->pengajuanId !== null) {
            return false;
        }

        $period = $this->period();

        if ($period === null) {
            Notification::make()->title(__('pkl.pengajuan.no_active_period'))->danger()->send();

            return false;
        }

        $data = $this->validatedData();

        if ($data === null) {
            return false;
        }

        try {
            $pengajuan = app(SaveDraft::class)->handle(
                auth()->user(),
                $period,
                $data,
                $this->pengajuanId === null ? null : $this->pengajuan(),
            );
        } catch (ValidationException $exception) {
            $this->reportValidation($exception);

            return false;
        }

        $this->pengajuanId = (int) $pengajuan->getKey();
        $this->companyId = (int) $pengajuan->company_id;
        $this->similarityConfirmed = false;
        $this->similarityWarnings = [];

        if ($notify) {
            Notification::make()->title(__('pkl.pengajuan.saved'))->success()->send();
        }

        return true;
    }

    public function submit(): void
    {
        if (! $this->saveDraft(false)) {
            return;
        }

        $pengajuan = $this->pengajuan();

        if ($pengajuan === null) {
            return;
        }

        try {
            app(SubmitPengajuan::class)->handle($pengajuan, auth()->user());
        } catch (ValidationException $exception) {
            $this->reportValidation($exception);

            return;
        }

        $this->pengajuanId = (int) $pengajuan->getKey();

        Notification::make()->title(__('pkl.pengajuan.submitted'))->success()->send();
    }

    public function reopen(): void
    {
        $pengajuan = $this->pengajuan();

        if ($pengajuan === null) {
            return;
        }

        app(ReopenPengajuan::class)->handle($pengajuan, auth()->user());

        $this->companyId = (int) $pengajuan->company_id;

        Notification::make()->title(__('pkl.pengajuan.reopened'))->success()->send();
    }

    public function startNew(): void
    {
        $this->pengajuanId = null;
        $this->companyId = null;
        $this->newCompany = false;
        $this->company = [
            'nama' => null,
            'alamat' => null,
            'email' => null,
            'website' => null,
            'telp_fax' => null,
            'cp_name' => null,
            'cp_phone' => null,
        ];
    }

    public function confirmSimilarity(): void
    {
        $this->similarityConfirmed = true;
        $this->dispatch('close-modal', id: 'konfirmasi-kemiripan');

        $this->saveDraft();
    }

    /**
     * @return array<string, mixed>|null
     */
    private function validatedData(): ?array
    {
        try {
            $this->validate();
        } catch (ValidationException $exception) {
            $this->reportValidation($exception);

            return null;
        }

        if (! $this->newCompany) {
            return ['company_id' => $this->companyId];
        }

        $input = array_filter(
            $this->company,
            fn ($value): bool => filled($value),
        );

        if (! $this->similarityConfirmed) {
            $check = app(CompanyResolver::class)->inspect($input);

            if ($check->hasWarnings()) {
                $this->similarityWarnings = $check->warnings;
                $this->dispatch('open-modal', id: 'konfirmasi-kemiripan');

                return null;
            }
        }

        return ['company' => $input];
    }

    /** @return array<string, mixed> */
    protected function rules(): array
    {
        return [
            'companyId' => $this->newCompany ? ['nullable'] : ['required', 'integer', 'exists:companies,id'],
            'company.nama' => [$this->newCompany ? 'required' : 'nullable', 'string', 'max:200'],
            'company.alamat' => [$this->newCompany ? 'required' : 'nullable', 'string'],
            'company.email' => ['nullable', 'email', 'max:150'],
            'company.website' => ['nullable', 'string', 'max:200'],
            'company.telp_fax' => ['nullable', 'string', 'max:30'],
            'company.cp_name' => ['nullable', 'string', 'max:150'],
            'company.cp_phone' => ['nullable', 'string', 'max:30'],
        ];
    }

    private function reportValidation(ValidationException $exception): void
    {
        foreach ($exception->errors() as $field => $messages) {
            $this->addError($field, $messages[0]);
        }

        Notification::make()
            ->title('Pengajuan belum dapat disimpan')
            ->body($exception->errors()[array_key_first($exception->errors())][0] ?? '')
            ->danger()
            ->send();
    }
}
