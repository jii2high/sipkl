<?php

namespace App\Livewire\Eksternal;

use App\Actions\Industri\SubmitIndustryAssessment;
use App\Models\ExternalSession;
use App\Models\FormSection;
use App\Models\FormSubmission;
use App\Models\FormSubmissionSiswa;
use App\Services\MagicLinkService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Form penilaian industri (F10): satu submission untuk semua siswa pivot pada
 * perusahaan itu. Setelah submit final, halaman hanya menampilkan status terkunci
 * (tanpa revisi).
 */
#[Layout('components.layouts.eksternal')]
#[Title('Penilaian industri')]
class IndustryAssessment extends Component
{
    public int $sessionId;

    public ?int $submissionId = null;

    public bool $done = false;

    public ?string $notice = null;

    /** @var array<int, string> */
    public array $items = [];

    /** @var array<int, string> */
    public array $alokasi = [];

    /** @var array<string, int|string> */
    public array $kehadiran = ['sakit' => 0, 'izin' => 0, 'tanpa_keterangan' => 0];

    public string $catatan = '';

    public function mount(Request $request): void
    {
        $session = $request->attributes->get('external_session');

        if (! $session instanceof ExternalSession) {
            abort(410);
        }

        $link = $session->magicLink;

        if ($link === null || $link->type !== MagicLinkService::TYPE_INDUSTRY_ASSESSMENT) {
            abort(410);
        }

        $submission = FormSubmission::query()->find($link->reference_id);
        $this->sessionId = $session->getKey();
        $this->submissionId = $submission?->getKey();

        if ($submission === null) {
            $this->done = true;
            $this->notice = __('pkl.eksternal.invalid_link');

            return;
        }

        if ($submission->is_submitted) {
            $this->done = true;
            $this->notice = __('pkl.industri.already_submitted');
        }
    }

    public function submission(): ?FormSubmission
    {
        return $this->submissionId === null
            ? null
            : FormSubmission::query()
                ->with(['company', 'definition', 'students.pengajuanSiswa.siswa'])
                ->find($this->submissionId);
    }

    /** @return Collection<int, FormSection> */
    public function sections()
    {
        return $this->submission()?->definition?->sections()->with('items')->get() ?? collect();
    }

    /** @return Collection<int, FormSubmissionSiswa> */
    public function students()
    {
        return $this->submission()?->students ?? collect();
    }

    public function submit(): void
    {
        $session = ExternalSession::query()->find($this->sessionId);
        $submission = $session === null
            ? null
            : FormSubmission::query()->find($session->magicLink?->reference_id);

        if ($submission === null || $session === null) {
            return;
        }

        try {
            app(SubmitIndustryAssessment::class)->handle($submission, [
                'items' => $this->items,
                'alokasi' => $this->alokasi,
                'kehadiran' => $this->kehadiran,
                'catatan' => $this->catatan,
            ], $session);
        } catch (ValidationException $exception) {
            $this->addError('items', collect($exception->errors())->flatten()->first() ?? '');

            return;
        } catch (AuthorizationException $exception) {
            $this->addError('items', $exception->getMessage());

            return;
        }

        $this->done = true;
        $this->notice = __('pkl.industri.score_saved');
    }

    public function render(): mixed
    {
        return view('livewire.eksternal.industry-assessment');
    }
}
