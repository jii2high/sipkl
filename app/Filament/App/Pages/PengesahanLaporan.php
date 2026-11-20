<?php

namespace App\Filament\App\Pages;

use App\Actions\Pengesahan\BulkApproveSignatures;
use App\Actions\Pengesahan\IssueSignerLink;
use App\Actions\Pengesahan\PengesahanAction;
use App\Actions\Pengesahan\ReissueSignerLink;
use App\Actions\Pengesahan\RejectStep;
use App\Actions\Pengesahan\SignStep;
use App\Enums\Role;
use App\Exceptions\DocumentIssuanceHeld;
use App\Models\DocumentSignature;
use App\Models\MagicLink;
use App\Services\MagicLinkService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Validation\ValidationException;

/**
 * Antrean pengesahan internal (F7): Kaprog menandatangani step 2 dan menerbitkan
 * tautan step 3/4; Kepsek menandatangani step 5. Bulk memproses tiap laporan
 * independen dan menampilkan hasil per item.
 */
class PengesahanLaporan extends Page
{
    protected string $view = 'filament.app.pages.pengesahan-laporan';

    protected static ?string $navigationLabel = 'Pengesahan laporan';

    protected static ?string $title = 'Pengesahan Laporan 5-TTD';

    protected static ?int $navigationSort = 6;

    /** @var array<int, int|string> */
    public array $bulkIds = [];

    /** @var array<int, string> */
    public array $rejectNotes = [];

    /** @var array<int, string> */
    public array $reasons = [];

    /** @var array{approved: array<int, int>, failed: array<int, array{id: int, alasan: string}>}|null */
    public ?array $bulkResult = null;

    public static function canAccess(): bool
    {
        return in_array(session('active_role'), [Role::Kaprog->value, Role::Kepsek->value], true);
    }

    /** @return array<int, DocumentSignature> */
    public function signatures(): array
    {
        $query = DocumentSignature::query()
            ->where('status', 'pending')
            ->with(['pengajuanSiswa.siswa', 'pengajuanSiswa.company', 'laporanVersion']);

        if (session('active_role') === Role::Kaprog->value) {
            $jurusanId = auth()->user()?->roleScopeId(Role::Kaprog);

            $query->whereIn('step_order', [2, 3, 4])
                ->whereHas('pengajuanSiswa.siswa', fn ($student) => $student->where('jurusan_id', $jurusanId));
        } else {
            $query->where('step_order', 5);
        }

        return $query->orderBy('step_order')->orderBy('id')->get()->all();
    }

    public function stepLabel(DocumentSignature $signature): string
    {
        return PengesahanAction::STEP_LABELS[(int) $signature->step_order] ?? '';
    }

    /** @return array<int, array{label: string, status: string, waiting: ?string, note: ?string}> */
    public function stepper(DocumentSignature $signature): array
    {
        return PengesahanAction::stepsFor((int) $signature->pengajuan_siswa_id);
    }

    public function linkState(DocumentSignature $signature): string
    {
        $type = $this->linkType($signature);

        if ($type === null) {
            return 'none';
        }

        $links = app(MagicLinkService::class);

        if ($links->activeFor($type, (int) $signature->getKey()) !== null) {
            return 'issued';
        }

        return MagicLink::query()
            ->where('type', $type)
            ->where('reference_id', $signature->getKey())
            ->exists() ? 'expired' : 'none';
    }

    public function approve(int $id): void
    {
        $this->run(
            fn () => app(SignStep::class)->handle(DocumentSignature::query()->findOrFail($id), auth()->user()),
            __('pkl.pengesahan.signed', ['step' => DocumentSignature::query()->find($id)?->step_order]),
        );
    }

    public function reject(int $id): void
    {
        $this->run(
            fn () => app(RejectStep::class)->handle(
                DocumentSignature::query()->findOrFail($id),
                (string) ($this->rejectNotes[$id] ?? ''),
                auth()->user(),
            ),
            __('pkl.pengesahan.rejected'),
        );
    }

    public function issue(int $id): void
    {
        $this->run(
            fn () => app(IssueSignerLink::class)->handle(auth()->user(), DocumentSignature::query()->findOrFail($id)),
            'Tautan pengesahan diterbitkan dan dikirim ke email signer.',
        );
    }

    public function reissue(int $id): void
    {
        $this->run(
            fn () => app(ReissueSignerLink::class)->handle(
                auth()->user(),
                DocumentSignature::query()->findOrFail($id),
                (string) ($this->reasons[$id] ?? ''),
            ),
            'Tautan lama dicabut dan tautan baru dikirim.',
        );
    }

    public function bulkApprove(): void
    {
        $result = app(BulkApproveSignatures::class)->handle(auth()->user(), $this->bulkIds);

        $this->bulkResult = $result;
        $this->bulkIds = [];

        Notification::make()
            ->title(count($result['approved']).' laporan disetujui, '.count($result['failed']).' gagal.')
            ->status($result['failed'] === [] ? 'success' : 'warning')
            ->send();
    }

    private function run(callable $action, string $success): bool
    {
        try {
            $action();
        } catch (ValidationException $exception) {
            Notification::make()
                ->title(collect($exception->errors())->flatten()->first() ?? __('pkl.pengesahan.not_authorized'))
                ->danger()
                ->send();

            return false;
        } catch (DocumentIssuanceHeld $exception) {
            Notification::make()->title($exception->getMessage())->warning()->send();

            return false;
        }

        Notification::make()->title($success)->success()->send();

        return true;
    }

    private function linkType(DocumentSignature $signature): ?string
    {
        return match ((int) $signature->step_order) {
            3 => MagicLinkService::TYPE_SIGNER_STEP3,
            4 => MagicLinkService::TYPE_SIGNER_STEP4,
            default => null,
        };
    }
}
