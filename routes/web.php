<?php

use App\Http\Controllers\Auth\FirstLoginController;
use App\Http\Controllers\Hubin\AkunController;
use App\Http\Controllers\Hubin\ImportExportController;
use App\Http\Controllers\Hubin\IndikatorPenilaianController;
use App\Http\Controllers\Hubin\KompetensiKeahlianController;
use App\Http\Controllers\Hubin\PeriodePklController;
use App\Http\Controllers\Hubin\PerusahaanController;
use App\Http\Controllers\Hubin\SectionPenilaianController;
use App\Http\Controllers\Kaprog\VerifikasiController;
use App\Http\Controllers\Siswa\PengajuanController;
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
        Route::get('pengajuan', [PengajuanController::class, 'index'])->name('pengajuan.index');
        Route::get('pengajuan/create', [PengajuanController::class, 'create'])->name('pengajuan.create');
        Route::post('pengajuan', [PengajuanController::class, 'store'])->name('pengajuan.store');
        Route::get('riwayat', [PengajuanController::class, 'riwayat'])->name('riwayat');
    });

    Route::prefix('kaprog')->name('kaprog.')->group(function () {
        Route::inertia('dashboard', 'kaprog/dashboard')->name('dashboard');
        Route::get('verifikasi', [VerifikasiController::class, 'index'])->name('verifikasi.index');
        Route::get('verifikasi/{pengajuan}', [VerifikasiController::class, 'show'])->name('verifikasi.show');
        Route::post('verifikasi/{pengajuan}/approve', [VerifikasiController::class, 'approve'])->name('verifikasi.approve');
        Route::post('verifikasi/{pengajuan}/reject', [VerifikasiController::class, 'reject'])->name('verifikasi.reject');
    });

    Route::prefix('guru-pembimbing')->name('guru-pembimbing.')->group(function () {
        Route::inertia('dashboard', 'guru-pembimbing/dashboard')->name('dashboard');
    });

    Route::prefix('hubin')->name('hubin.')->group(function () {
        Route::inertia('dashboard', 'hubin/dashboard')->name('dashboard');

        Route::get('perusahaan', [PerusahaanController::class, 'index'])->name('perusahaan.index');
        Route::post('perusahaan', [PerusahaanController::class, 'store'])->name('perusahaan.store');
        Route::put('perusahaan/{perusahaan}', [PerusahaanController::class, 'update'])->name('perusahaan.update');
        Route::delete('perusahaan/{perusahaan}', [PerusahaanController::class, 'destroy'])->name('perusahaan.destroy');

        Route::get('periode-pkl', [PeriodePklController::class, 'index'])->name('periode-pkl.index');
        Route::post('periode-pkl', [PeriodePklController::class, 'store'])->name('periode-pkl.store');
        Route::put('periode-pkl/{periodePkl}', [PeriodePklController::class, 'update'])->name('periode-pkl.update');
        Route::delete('periode-pkl/{periodePkl}', [PeriodePklController::class, 'destroy'])->name('periode-pkl.destroy');

        Route::get('kompetensi-keahlian', [KompetensiKeahlianController::class, 'index'])->name('kompetensi-keahlian.index');
        Route::post('kompetensi-keahlian', [KompetensiKeahlianController::class, 'store'])->name('kompetensi-keahlian.store');
        Route::put('kompetensi-keahlian/{kompetensiKeahlian}', [KompetensiKeahlianController::class, 'update'])->name('kompetensi-keahlian.update');
        Route::delete('kompetensi-keahlian/{kompetensiKeahlian}', [KompetensiKeahlianController::class, 'destroy'])->name('kompetensi-keahlian.destroy');

        Route::get('indikator-penilaian', [IndikatorPenilaianController::class, 'index'])->name('indikator-penilaian.index');
        Route::post('indikator-penilaian', [IndikatorPenilaianController::class, 'store'])->name('indikator-penilaian.store');
        Route::put('indikator-penilaian/{indikatorPenilaian}', [IndikatorPenilaianController::class, 'update'])->name('indikator-penilaian.update');
        Route::delete('indikator-penilaian/{indikatorPenilaian}', [IndikatorPenilaianController::class, 'destroy'])->name('indikator-penilaian.destroy');

        Route::get('section-penilaian', [SectionPenilaianController::class, 'index'])->name('section-penilaian.index');
        Route::post('section-penilaian', [SectionPenilaianController::class, 'store'])->name('section-penilaian.store');
        Route::put('section-penilaian/{sectionPenilaian}', [SectionPenilaianController::class, 'update'])->name('section-penilaian.update');
        Route::delete('section-penilaian/{sectionPenilaian}', [SectionPenilaianController::class, 'destroy'])->name('section-penilaian.destroy');

        Route::get('import-export', [ImportExportController::class, 'index'])->name('import-export.index');
        Route::get('import-export/akun', [ImportExportController::class, 'exportAkun'])->name('import-export.akun.export');
        Route::get('import-export/guru-template', [ImportExportController::class, 'exportGuruTemplate'])->name('import-export.guru.template');
        Route::get('import-export/siswa-template', [ImportExportController::class, 'exportSiswaTemplate'])->name('import-export.siswa.template');
        Route::post('import-export/guru', [ImportExportController::class, 'importGuru'])->name('import-export.guru.import');
        Route::post('import-export/siswa', [ImportExportController::class, 'importSiswa'])->name('import-export.siswa.import');

        Route::get('akun', [AkunController::class, 'index'])->name('akun.index');
        Route::post('akun/{guruProfile}/nonaktifkan-kaprog', [AkunController::class, 'nonaktifkanKaprog'])->name('akun.nonaktifkan-kaprog');
    });
});

require __DIR__.'/settings.php';
