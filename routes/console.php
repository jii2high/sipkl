<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// BR13: auto Selesai PKL — cek harian, karena syarat "periode berakhir" tidak bergantung request.
Schedule::command('pkl:tandai-selesai')->daily();
