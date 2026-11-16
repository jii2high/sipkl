<?php

use App\Http\Controllers\PrivateFileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

/*
| Login kustom (PRD F1) dibangun pada M1. Route bernama `login` dibutuhkan sekarang
| karena panel Filament tidak memakai halaman login bawaannya.
*/
Route::get('/login', function () {
    return view('auth.login-placeholder');
})->name('login');

/*
| Satu-satunya jalur unduh dokumen private (AC-49). Otorisasi per objek lewat
| Policy `download-private-file`; tidak ada URL publik ke storage/app/private.
*/
Route::get('/files/{path}', PrivateFileController::class)
    ->where('path', '.*')
    ->middleware('auth')
    ->name('files.download');
