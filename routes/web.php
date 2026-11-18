<?php

use App\Http\Controllers\PrivateFileController;
use App\Http\Controllers\SignerEmailVerificationController;
use App\Livewire\Public\ForcePasswordReset;
use App\Livewire\Public\Login;
use App\Livewire\Public\VerifyOtp;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/login', Login::class)->name('login');
Route::get('/verify-otp', VerifyOtp::class)->name('verify-otp');
Route::get('/force-password-reset', ForcePasswordReset::class)->name('force-password-reset');

/*
| Verifikasi email signer industri (TASKS M2.8). Signed URL berbatas waktu,
| bisa diakses tanpa login karena signer tidak punya akun.
*/
Route::get('/verifikasi-signer/{company}/{tipe}', SignerEmailVerificationController::class)
    ->whereNumber('company')
    ->whereIn('tipe', ['pembimbing', 'kepala'])
    ->middleware('signed')
    ->name('signer.verifikasi');

/*
| Satu-satunya jalur unduh dokumen private (AC-49). Otorisasi per objek lewat
| Policy `download-private-file`; tidak ada URL publik ke storage/app/private.
*/
Route::get('/files/{path}', PrivateFileController::class)
    ->where('path', '.*')
    ->middleware('auth')
    ->name('files.download');
