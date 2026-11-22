<?php

use App\Jobs\ExpireMagicLinksJob;
use App\Jobs\ExpireVisitasiJob;
use App\Jobs\IndustryAssessmentReminderJob;
use App\Jobs\ProvisionalReminderJob;
use App\Jobs\RelockScoreJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Pengingat provisional harian (BR-F3-4). Jam operasional memakai Asia/Jakarta.
Schedule::job(new ProvisionalReminderJob)
    ->dailyAt('07:00')
    ->timezone('Asia/Jakarta')
    ->name('pengingat-provisional');

// Pengingat H-7 penilaian industri, hanya bila tautan belum dibuat (KP-F10-4).
Schedule::job(new IndustryAssessmentReminderJob)
    ->dailyAt('07:05')
    ->timezone('Asia/Jakarta')
    ->name('pengingat-penilaian-industri');

Schedule::job(new ExpireVisitasiJob)
    ->dailyAt('01:00')
    ->timezone('Asia/Jakarta')
    ->name('kedaluwarsa-visitasi');

Schedule::job(new ExpireMagicLinksJob)
    ->hourly()
    ->name('kedaluwarsa-magic-link');

// Window revisi nilai sidang ditutup otomatis setelah 24 jam (KP-F9-4).
Schedule::job(new RelockScoreJob)
    ->hourly()
    ->name('kunci-ulang-nilai-sidang');
