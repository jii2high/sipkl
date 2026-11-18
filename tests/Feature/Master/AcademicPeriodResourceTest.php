<?php

use App\Filament\Hubin\Resources\AcademicPeriods\Pages\ManageAcademicPeriods;
use App\Models\AcademicPeriod;
use App\Models\Company;
use App\Models\PengajuanPkl;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('hubin'));

    $this->actingAs(pklUser(['hubin']))->withSession(['active_role' => 'hubin']);
});

test('hubin dapat membuka daftar periode akademik', function () {
    AcademicPeriod::factory()->create(['tahun_ajaran' => '2025/2026']);

    $this->get('/hubin/academic-periods')
        ->assertOk()
        ->assertSee('2025/2026');
});

test('tahun ajaran dengan format salah ditolak', function () {
    Livewire\Livewire::test(ManageAcademicPeriods::class)
        ->callAction('create', data: [
            'tahun_ajaran' => '2025-2026',
            'batch' => '1',
            'tanggal_mulai' => '2026-01-05',
            'tanggal_selesai' => '2026-04-30',
        ])
        ->assertHasFormErrors(['tahun_ajaran']);

    expect(AcademicPeriod::query()->count())->toBe(0);
});

test('periode baru tersimpan', function () {
    Livewire\Livewire::test(ManageAcademicPeriods::class)
        ->callAction('create', data: [
            'tahun_ajaran' => '2026/2027',
            'batch' => '2',
            'tanggal_mulai' => '2026-07-01',
            'tanggal_selesai' => '2026-12-20',
            'is_active' => true,
        ])
        ->assertHasNoFormErrors();

    expect(AcademicPeriod::query()->where('tahun_ajaran', '2026/2027')->exists())->toBeTrue();
});

test('mengubah kunci pengajuan lewat edit', function () {
    $period = AcademicPeriod::factory()->create(['locked_for_new_submission' => false]);

    Livewire\Livewire::test(ManageAcademicPeriods::class)
        ->callAction(TestAction::make('edit')->table($period), data: [
            'tahun_ajaran' => $period->tahun_ajaran,
            'batch' => $period->batch,
            'tanggal_mulai' => $period->tanggal_mulai->toDateString(),
            'tanggal_selesai' => $period->tanggal_selesai->toDateString(),
            'is_active' => true,
            'locked_for_new_submission' => true,
        ])
        ->assertHasNoFormErrors();

    expect($period->refresh()->locked_for_new_submission)->toBeTrue();
});

test('action hapus disembunyikan untuk periode yang sudah punya pengajuan', function () {
    $period = AcademicPeriod::factory()->create();
    PengajuanPkl::query()->create([
        'company_id' => Company::factory()->create()->id,
        'academic_period_id' => $period->id,
    ]);

    Livewire\Livewire::test(ManageAcademicPeriods::class)
        ->assertActionHidden(TestAction::make('delete')->table($period));
});
