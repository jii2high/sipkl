<?php

namespace App\Livewire\Eksternal;

use App\Actions\Sidang\OpenSidangSubmission;
use App\Actions\Sidang\RequestUnlock;
use App\Actions\Sidang\SubmitScore;
use App\Models\ExternalSession;
use App\Models\FormSection;
use App\Models\FormSubmission;
use App\Models\JadwalSidang;
use App\Models\NilaiSidangRevision;
use App\Services\MagicLinkService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Form nilai sidang untuk penguji (F9 alur 3/5). Satu tautan berlaku untuk semua
 * siswa penguji tersebut pada hari sidang; setelah satu nilai dikunci form pindah
 * ke siswa berikutnya yang belum dinilai.
 */
#[Layout('components.layouts.eksternal')]
#[Title('Penilaian sidang')]
class ExaminerScore extends Component
{
    public int $sessionId;

    public string $linkType;

    public ?int $jadwalId = null;

    public ?int $submissionId = null;

    public bool $done = false;

    public ?string $notice = null;

    /** @var array<int, string> */
    public array $items = [];

    /** @var array<string, int|string> */
    public array $kehadiran = ['sakit' => 0, 'izin' => 0, 'tanpa_keterangan' => 0];

    public string $catatan = '';

    public string $reason = '';

    public ?int $anchorJadwalId = null;

    public function mount(Request $request): void
    {
        $session = $request->attributes->get('external_session');

        if (! $session instanceof ExternalSession) {
            abort(410);
        }

        $link = $session->magicLink;

        if ($link === null) {
            abort(410);
        }

        $this->sessionId = $session->getKey();
        $this->linkType = (string) $link->type;

        if ($this->linkType === MagicLinkService::TYPE_EXAMINER_UNLOCK) {
            $revision = NilaiSidangRevision::query()->find($link->reference_id);

            if ($revision === null || $revision->status !== 'menunggu_revisi' || $revision->unlock_expires_at?->isPast()) {
                $this->done = true;
                $this->notice = __('pkl.sidang.revision_not_pending');

                return;
            }

            $this->loadSubmission($revision->submission);

            return;
        }

        $this->anchorJadwalId = (int) $link->reference_id;
        $first = $this->jadwals()->first();

        if ($first === null) {
            $this->done = true;
            $this->notice = __('pkl.sidang.examiner_missing');

            return;
        }

        $this->select($first->getKey());
    }

    /** @return Collection<int, JadwalSidang> */
    public function jadwals()
    {
        $anchor = JadwalSidang::query()->find($this->anchorJadwalId);

        if ($anchor === null) {
            return collect();
        }

        return JadwalSidang::query()
            ->with('pengajuanSiswa.siswa')
            ->where('external_examiner_id', $anchor->external_examiner_id)
            ->where('tanggal', $anchor->tanggal->toDateString())
            ->orderBy('urutan')
            ->get();
    }

    public function submission(): ?FormSubmission
    {
        return $this->submissionId === null
            ? null
            : FormSubmission::query()->find($this->submissionId);
    }

    /** @return Collection<int, FormSection> */
    public function sections()
    {
        $submission = $this->submission();

        return $submission?->definition?->sections()->with('items')->get() ?? collect();
    }

    public function select(int $jadwalId): void
    {
        if ($this->linkType !== MagicLinkService::TYPE_EXAMINER_SIDANG) {
            return;
        }

        try {
            $submission = app(OpenSidangSubmission::class)->handle(
                JadwalSidang::query()->findOrFail($jadwalId),
            );
        } catch (ValidationException $exception) {
            $this->notice = collect($exception->errors())->flatten()->first();

            return;
        }

        $this->loadSubmission($submission);
    }

    public function submit(): void
    {
        $submission = $this->submission();

        if ($submission === null) {
            return;
        }

        try {
            app(SubmitScore::class)->handle($submission, [
                'items' => $this->items,
                'kehadiran' => $this->kehadiran,
                'catatan' => $this->catatan,
            ], ExternalSession::query()->findOrFail($this->sessionId));
        } catch (ValidationException $exception) {
            $this->addError('items', collect($exception->errors())->flatten()->first() ?? '');

            return;
        } catch (AuthorizationException $exception) {
            $this->addError('items', $exception->getMessage());

            return;
        }

        $this->notice = $submission->refresh()->revision_count > 0
            ? __('pkl.sidang.score_revised')
            : __('pkl.sidang.score_saved');

        $this->advanceFrom($submission->getKey());
    }

    public function requestRevision(): void
    {
        $this->validate(['reason' => ['required', 'string', 'min:10', 'max:2000']], [
            'reason.min' => __('pkl.sidang.reason_min'),
        ]);

        $submission = $this->submission();

        if ($submission === null) {
            return;
        }

        try {
            app(RequestUnlock::class)->handle(
                $submission,
                ExternalSession::query()->findOrFail($this->sessionId),
                $this->reason,
            );
        } catch (ValidationException $exception) {
            $this->addError('reason', collect($exception->errors())->flatten()->first() ?? '');

            return;
        } catch (AuthorizationException $exception) {
            $this->addError('reason', $exception->getMessage());

            return;
        }

        $this->reason = '';
        $this->notice = __('pkl.sidang.unlock_requested');
    }

    /** Pindah ke siswa berikutnya yang belum dikunci; selesai bila tidak ada. */
    private function advanceFrom(int $justScored): void
    {
        if ($this->linkType !== MagicLinkService::TYPE_EXAMINER_SIDANG) {
            $this->done = true;

            return;
        }

        foreach ($this->jadwals() as $jadwal) {
            if ((int) $jadwal->getKey() === (int) $this->jadwalId) {
                continue;
            }

            $submission = FormSubmission::query()->where('jadwal_sidang_id', $jadwal->getKey())->first();

            if ($submission !== null && $submission->is_submitted) {
                continue;
            }

            $this->select($jadwal->getKey());

            return;
        }

        $this->done = true;
    }

    private function loadSubmission(?FormSubmission $submission): void
    {
        if ($submission === null) {
            $this->done = true;
            $this->notice = __('pkl.sidang.examiner_missing');

            return;
        }

        $submission->loadMissing(['definition.sections.items', 'jadwalSidang.pengajuanSiswa.siswa']);

        $this->submissionId = $submission->getKey();
        $this->jadwalId = $submission->jadwal_sidang_id;
        $this->done = false;

        $jawaban = (array) $submission->jawaban;
        $this->items = collect($jawaban['items'] ?? [])
            ->map(fn (array $value): string => (string) ($value['nilai'] ?? ''))
            ->all();

        foreach (array_keys($this->kehadiran) as $field) {
            $this->kehadiran[$field] = (int) data_get($jawaban, 'kehadiran.'.$field, 0);
        }

        $this->catatan = (string) ($jawaban['catatan'] ?? '');
    }

    /** Identitas siswa pada submission aktif. */
    public function studentName(): string
    {
        return (string) ($this->submission()?->jadwalSidang?->pengajuanSiswa?->siswa?->name ?? '-');
    }

    public function render(): mixed
    {
        return view('livewire.eksternal.examiner-score');
    }
}
