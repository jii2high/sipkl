<?php

use App\Http\Controllers\DocumentDownloadController;
use App\Http\Controllers\ThemePreferenceController;
use App\Http\Controllers\VisitasiDocumentationDownloadController;
use App\Livewire\Auth\ForcePasswordChange;
use App\Livewire\Auth\Login;
use App\Livewire\Auth\OtpChallenge;
use App\Livewire\Public\VerifyDocument;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => view('public.home'))->name('home');

// Pintu masuk tunggal (PRD F1, TRD.md §7.1). Panel Filament tidak punya login sendiri.
Route::get('/login', Login::class)->name('login');
Route::get('/login/otp', OtpChallenge::class)->name('login.otp');
Route::get('/login/password', ForcePasswordChange::class)->name('login.password');

// Verifikasi QR surat (BR-F12-3). Publik, dibatasi per IP (TRD.md §16).
Route::get('/verify/{token}', VerifyDocument::class)
    ->middleware('throttle:'.config('pkl.document.verify_throttle'))
    ->name('verify');

Route::middleware('auth')->group(function (): void {
    // Preferensi tema per akun (DESIGN.md "Perilaku tema").
    Route::post('/theme', ThemePreferenceController::class)->name('theme.update');

    // Unduhan surat dari disk privat; otorisasi lewat DocumentPolicy (TRD.md §7.4).
    Route::get('/documents/{jenis}/{id}', DocumentDownloadController::class)
        ->whereNumber('id')
        ->name('documents.download');

    Route::get('/visitasi-dokumentasi/{id}', VisitasiDocumentationDownloadController::class)
        ->whereNumber('id')
        ->name('visitasi.documentation.download');
});
