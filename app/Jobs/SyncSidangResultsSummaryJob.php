<?php

namespace App\Jobs;

use App\Actions\Sidang\SyncSidangResultsSummary;
use App\Models\FormSubmission;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

/**
 * Segarkan `sidang_results_summary` setelah nilai sidang dikunci atau direvisi
 * (TRD.md Lampiran 15) tanpa menahan request penguji.
 */
class SyncSidangResultsSummaryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public function __construct(public readonly int $submissionId) {}

    public function handle(): void
    {
        $submission = FormSubmission::query()->find($this->submissionId);

        if ($submission !== null) {
            app(SyncSidangResultsSummary::class)->handle($submission);
        }
    }
}
