<?php

use App\Actions\Action;
use App\Models\AcademicPeriod;
use App\Models\Company;
use App\Models\PengajuanPkl;
use Spatie\Activitylog\Models\Activity;
use Tests\Support\ApproveGroupAction;

function group(): PengajuanPkl
{
    return PengajuanPkl::query()->create([
        'company_id' => Company::factory()->create()->id,
        'academic_period_id' => AcademicPeriod::factory()->create()->id,
    ]);
}

test('Action dipanggil dua kali hanya menghasilkan satu efek', function () {
    $pkl = group();
    $action = new ApproveGroupAction;

    expect($action->handle($pkl))->toBe(1)
        ->and($action->handle($pkl))->toBe(0)
        ->and($pkl->newQuery()->findOrFail($pkl->id)->status)->toBe('surat_terbit')
        ->and(Activity::query()->count())->toBe(1);
});

test('audit dibatalkan bersama transaksi yang gagal', function () {
    $pkl = group();

    $gagal = new class extends Action
    {
        public function handle(mixed ...$arguments): mixed
        {
            $pkl = $arguments[0];

            return $this->transaction(function () use ($pkl): void {
                $this->transitionStatus($pkl, 'surat_terbit', 'terbentuk');
                $this->audit($pkl, 'status_diubah', 'terbentuk', 'surat_terbit');

                throw new RuntimeException('gagal setelah audit');
            });
        }
    };

    expect(fn () => $gagal->handle($pkl))->toThrow(RuntimeException::class)
        ->and($pkl->newQuery()->findOrFail($pkl->id)->status)->toBe('terbentuk')
        ->and(Activity::query()->count())->toBe(0);
});
