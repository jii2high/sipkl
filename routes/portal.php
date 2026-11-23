<?php

use App\Livewire\Portal\AntreanWalas;
use App\Livewire\Portal\BimbinganSiswa;
use App\Livewire\Portal\JadwalSidang;
use App\Livewire\Portal\KanbanKelas;
use App\Livewire\Portal\PengajuanSiswa;
use App\Livewire\Portal\PengesahanKepsek;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Portal Mobile (Blade + Livewire, tanpa Filament)
|--------------------------------------------------------------------------
| Satu rute per peran. Rute portal sengaja di luar grup middleware panel
| Filament (TRD.md §7.1); `/login` tetap satu pintu untuk semua peran.
*/

Route::middleware(['auth', 'portal.role:siswa'])->group(function (): void {
    Route::get('/siswa', PengajuanSiswa::class)->name('portal.siswa');
    Route::get('/siswa/bimbingan', BimbinganSiswa::class)->name('portal.siswa.bimbingan');
    Route::get('/siswa/jadwal-sidang', JadwalSidang::class)->name('portal.siswa.jadwal-sidang');
});

Route::middleware(['auth', 'portal.role:walas'])->group(function (): void {
    Route::get('/walas', AntreanWalas::class)->name('portal.walas');
    Route::get('/walas/kanban', KanbanKelas::class)->name('portal.walas.kanban');
});

Route::middleware(['auth', 'portal.role:kepsek'])->group(function (): void {
    Route::get('/kepsek', PengesahanKepsek::class)->name('portal.kepsek');
});
