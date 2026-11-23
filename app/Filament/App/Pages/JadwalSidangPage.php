<?php

namespace App\Filament\App\Pages;

use App\Actions\Sidang\IssueExaminerLink;
use App\Actions\Sidang\SaveExternalExaminer;
use App\Actions\Sidang\ScheduleSidang;
use App\Actions\Sidang\ShiftSidang;
use App\Enums\Role;
use App\Models\AcademicPeriod;
use App\Models\ExternalExaminer;
use App\Models\JadwalSidang;
use App\Models\PengajuanSiswa;
use App\Services\MagicLinkService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/**
 * Penjadwalan sidang Kaprog (F9 alur 1/2/4): tetapkan penguji, jadwalkan siswa
 * dari pool Siap Sidang, terbitkan tautan penguji, lalu geser atau batalkan satu
 * jadwal dengan cascade shift.
 */
class JadwalSidangPage extends Page
{
    protected string $view = 'filament.app.pages.jadwal-sidang';

    protected static ?string $navigationLabel = 'Jadwal sidang';

    protected static ?string $slug = 'jadwal-sidang';

    protected static ?string $title = 'Jadwal Sidang';

    protected static ?int $navigationSort = 7;

    public ?int $periodId = null;

    public ?int $pengajuanId = null;

    public ?int $examinerId = null;

    public ?string $tanggal = null;

    public string $nama = '';

    public string $contactType = 'email';

    public string $contactValue = '';

    public string $nik = '';

    public ?int $shiftId = null;

    public string $shiftTanggal = '';

    public ?int $revokeId = null;

    public string $revokeReason = '';

    public ?string $waUrl = null;

    public static function canAccess(): bool
    {
        return session('active_role') === Role::Kaprog->value;
    }

    public function mount(): void
    {
        $this->periodId = AcademicPeriod::query()
            ->where('is_active', true)
            ->orderByDesc('tanggal_mulai')
            ->value('id');
        $this->tanggal = now('Asia/Jakarta')->toDateString();
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

    /** @return Collection<int, ExternalExaminer> */
    public function examiners()
    {
        return ExternalExaminer::query()
            ->where('jurusan_id', auth()->user()->roleScopeId(Role::Kaprog))
            ->orderBy('nama')
            ->get();
    }

    /** Pool Siap Sidang yang belum punya jadwal. */
    public function pool()
    {
        if ($this->periodId === null) {
            return collect();
        }

        return PengajuanSiswa::query()
            ->with(['siswa', 'company'])
            ->where('academic_period_id', $this->periodId)
            ->siapSidang()
            ->whereHas('siswa', fn ($student) => $student->where('jurusan_id', auth()->user()->roleScopeId(Role::Kaprog)))
            ->whereDoesntHave('jadwalSidang')
            ->orderBy('id')
            ->get();
    }

    /** @return Collection<int, JadwalSidang> */
    public function schedules()
    {
        if ($this->periodId === null) {
            return collect();
        }

        return JadwalSidang::query()
            ->with(['pengajuanSiswa.siswa', 'externalExaminer', 'submission'])
            ->whereHas('pengajuanSiswa', fn ($pengajuan) => $pengajuan
                ->where('academic_period_id', $this->periodId)
                ->whereHas('siswa', fn ($student) => $student->where('jurusan_id', auth()->user()->roleScopeId(Role::Kaprog))))
            ->orderBy('tanggal')
            ->orderBy('urutan')
            ->get();
    }

    public function saveExaminer(): void
    {
        $this->validate([
            'nama' => ['required', 'string', 'max:150'],
            'contactType' => ['required', 'in:email,whatsapp'],
            'contactValue' => ['required', 'string', 'max:150'],
            'nik' => ['nullable', 'string', 'max:30'],
        ]);

        try {
            app(SaveExternalExaminer::class)->handle(
                $this->nama,
                $this->contactType,
                $this->contactValue,
                auth()->user(),
                $this->nik,
            );
        } catch (ValidationException $exception) {
            $this->showValidation($exception);

            return;
        }

        $this->reset('nama', 'contactValue', 'nik');
        Notification::make()->title('Penguji ditetapkan.')->success()->send();
    }

    public function schedule(): void
    {
        $this->validate([
            'pengajuanId' => ['required', 'integer', 'exists:pengajuan_siswa,id'],
            'examinerId' => ['required', 'integer', 'exists:external_examiners,id'],
            'tanggal' => ['required', 'date'],
        ]);

        try {
            app(ScheduleSidang::class)->handle(
                PengajuanSiswa::query()->findOrFail($this->pengajuanId),
                ExternalExaminer::query()->findOrFail($this->examinerId),
                $this->tanggal,
                auth()->user(),
            );
        } catch (ValidationException $exception) {
            $this->showValidation($exception);

            return;
        }

        $this->reset('pengajuanId', 'examinerId');
        Notification::make()->title(__('pkl.sidang.scheduled'))->success()->send();
    }

    public function issueLink(int $jadwalId): void
    {
        try {
            $result = app(IssueExaminerLink::class)->handle(
                JadwalSidang::query()->findOrFail($jadwalId),
                auth()->user(),
            );
        } catch (ValidationException $exception) {
            $this->showValidation($exception);

            return;
        }

        $this->waUrl = $result['wa_url'];
        Notification::make()
            ->title(__('pkl.sidang.link_issued'))
            ->body('PIN: '.$result['pin'].' — tautan juga dapat dikirim lewat WhatsApp.')
            ->success()
            ->persistent()
            ->send();
    }

    public function startShift(int $jadwalId): void
    {
        $jadwal = JadwalSidang::query()->findOrFail($jadwalId);
        $this->shiftId = $jadwal->getKey();
        $this->shiftTanggal = $jadwal->tanggal->toDateString();
    }

    public function startRevoke(int $jadwalId): void
    {
        $this->revokeId = $jadwalId;
        $this->revokeReason = '';
    }

    /** Cabut tautan aktif penguji; penerbitan ulang memakai tombol "Terbitkan tautan". */
    public function revokeLink(): void
    {
        $this->validate([
            'revokeId' => ['required', 'integer', 'exists:jadwal_sidang,id'],
            'revokeReason' => ['required', 'string', 'min:10', 'max:500'],
        ]);

        $jadwal = JadwalSidang::query()->findOrFail($this->revokeId);
        $link = app(MagicLinkService::class)->activeFor(
            MagicLinkService::TYPE_EXAMINER_SIDANG,
            (int) $jadwal->getKey(),
        );

        if ($link === null) {
            Notification::make()->title(__('pkl.sidang.link_missing'))->warning()->send();

            return;
        }

        app(MagicLinkService::class)->revoke($link, auth()->user(), $this->revokeReason);

        $this->reset('revokeId', 'revokeReason');
        Notification::make()->title('Tautan penguji dicabut.')->success()->send();
    }

    public function saveShift(): void
    {
        $this->validate([
            'shiftId' => ['required', 'integer', 'exists:jadwal_sidang,id'],
            'shiftTanggal' => ['nullable', 'date'],
        ]);

        try {
            app(ShiftSidang::class)->handle(
                JadwalSidang::query()->findOrFail($this->shiftId),
                $this->shiftTanggal === '' ? null : $this->shiftTanggal,
                auth()->user(),
            );
        } catch (ValidationException $exception) {
            $this->showValidation($exception);

            return;
        }

        $this->reset('shiftId', 'shiftTanggal');
        Notification::make()->title(__('pkl.sidang.moved'))->success()->send();
    }

    public function cancelShift(): void
    {
        $this->shiftTanggal = '';
        $this->saveShift();
    }

    private function showValidation(ValidationException $exception): void
    {
        foreach ($exception->errors() as $field => $messages) {
            $this->addError($field, $messages[0]);
        }
    }
}
