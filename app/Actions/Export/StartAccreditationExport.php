<?php

namespace App\Actions\Export;

use App\Actions\Action;
use App\Enums\Role;
use App\Jobs\AccreditationExportJob;
use App\Models\AccreditationExport;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use RuntimeException;

/**
 * Hubin memicu satu percobaan ekspor akreditasi (BR-F13-1, F13 alur). Baris
 * `queued` dibuat lebih dulu, job berjalan di queue; retry = percobaan baru yang
 * menunjuk `retry_of_id`, bukan mengulang baris lama (BR-F13-2).
 */
final class StartAccreditationExport extends Action
{
    public function handle(mixed ...$arguments): AccreditationExport
    {
        [$actor] = $arguments;
        $academicPeriodId = $arguments[1] ?? null;
        $retryOfId = $arguments[2] ?? null;

        if (! $actor instanceof User) {
            throw new RuntimeException('Aktor ekspor tidak valid.');
        }

        if (! $actor->hasRole(Role::Hubin)) {
            throw new AuthorizationException('Hanya Hubin yang dapat memulai ekspor akreditasi.');
        }

        $export = AccreditationExport::query()->create([
            'requested_by' => $actor->getKey(),
            'academic_period_id' => $academicPeriodId !== null ? (int) $academicPeriodId : null,
            'retry_of_id' => $retryOfId !== null ? (int) $retryOfId : null,
            'status' => 'queued',
            'requested_at' => now(),
        ]);

        $this->audit(
            $export,
            'accreditation_export_requested',
            null,
            'queued',
            ['periode' => $export->academic_period_id, 'retry_of' => $export->retry_of_id],
            'Ekspor akreditasi diminta',
        );

        AccreditationExportJob::dispatch((int) $export->getKey());

        return $export;
    }
}
