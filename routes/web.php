<?php

use App\Http\Controllers\PrivateFileController;
use App\Livewire\Public\Login;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/login', Login::class)->name('login');

/*
| Satu-satunya jalur unduh dokumen private (AC-49). Otorisasi per objek lewat
| Policy `download-private-file`; tidak ada URL publik ke storage/app/private.
*/
Route::get('/files/{path}', PrivateFileController::class)
    ->where('path', '.*')
    ->middleware('auth')
    ->name('files.download');
