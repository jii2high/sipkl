<?php

use App\Http\Controllers\ExternalLaporanDownloadController;
use App\Livewire\Eksternal\ExaminerEntry;
use App\Livewire\Eksternal\ExaminerScore;
use App\Livewire\Eksternal\RequestNewLink;
use App\Livewire\Eksternal\SignerEntry;
use App\Livewire\Eksternal\SignerStep;
use Illuminate\Support\Facades\Route;

/*
| Portal eksternal tanpa akun (TRD.md §9). Ruang lingkup hanya satu tugas dan
| terpisah dari sesi panel internal; guard `eksternal` membaca sesi OTP turunan
| tautan, bukan sesi login pengguna.
*/

Route::get('/eksternal/link', RequestNewLink::class)
    ->middleware('throttle:5,1')
    ->name('eksternal.link');

Route::get('/eksternal/masuk/{token}', SignerEntry::class)
    ->middleware('throttle:10,1')
    ->name('eksternal.masuk');

Route::get('/eksternal/penguji/{token}', ExaminerEntry::class)
    ->middleware('throttle:10,1')
    ->name('eksternal.penguji');

Route::middleware('eksternal')->group(function (): void {
    Route::get('/eksternal/tanda-tangan', SignerStep::class)->name('eksternal.ttd');

    Route::get('/eksternal/penguji-nilai', ExaminerScore::class)->name('eksternal.penguji.nilai');

    Route::get('/eksternal/laporan/{signature}', ExternalLaporanDownloadController::class)
        ->whereNumber('signature')
        ->name('eksternal.laporan');
});
