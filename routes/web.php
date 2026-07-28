<?php

use App\Http\Controllers\Auth\FirstLoginController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth'])->group(function () {
    Route::get('auth/force-change-password', [FirstLoginController::class, 'create'])->name('password.force-change');
    Route::post('auth/force-change-password', [FirstLoginController::class, 'store'])->name('password.force-change.update');
});

Route::middleware(['auth', 'verified', 'check.first.login'])->group(function () {
    Route::get('dashboard', function () {
        $user = request()->user();

        return match (true) {
            $user->hasRole('siswa') => redirect()->route('siswa.dashboard'),
            $user->hasRole('kaprog') => redirect()->route('kaprog.dashboard'),
            $user->hasRole('hubin') => redirect()->route('hubin.dashboard'),
            default => redirect()->route('guru-pembimbing.dashboard'),
        };
    })->name('dashboard');

    Route::prefix('siswa')->name('siswa.')->group(function () {
        Route::inertia('dashboard', 'siswa/dashboard')->name('dashboard');
    });

    Route::prefix('kaprog')->name('kaprog.')->group(function () {
        Route::inertia('dashboard', 'kaprog/dashboard')->name('dashboard');
    });

    Route::prefix('guru-pembimbing')->name('guru-pembimbing.')->group(function () {
        Route::inertia('dashboard', 'guru-pembimbing/dashboard')->name('dashboard');
    });

    Route::prefix('hubin')->name('hubin.')->group(function () {
        Route::inertia('dashboard', 'hubin/dashboard')->name('dashboard');
    });
});

require __DIR__.'/settings.php';
