<?php

use App\Http\Controllers\Auth\FirstLoginController;
use App\Http\Controllers\Hubin\AkunController;
use App\Http\Controllers\Hubin\IndikatorPenilaianController;
use App\Http\Controllers\Hubin\KompetensiKeahlianController;
use App\Http\Controllers\Hubin\PeriodePklController;
use App\Http\Controllers\Hubin\PerusahaanController;
use App\Http\Controllers\Hubin\SuratPengantarController;
use App\Http\Controllers\Kaprog\VerifikasiController;
use App\Http\Controllers\Siswa\PengajuanController as SiswaPengajuanController;
use App\Http\Controllers\Siswa\SuratBalasanController;
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
        Route::get('pengajuan', [SiswaPengajuanController::class, 'index'])->name('pengajuan.index')
            ->middleware('permission:pengajuan.ajukan');
        Route::post('pengajuan', [SiswaPengajuanController::class, 'store'])->name('pengajuan.store')
            ->middleware('permission:pengajuan.ajukan');
        Route::post('pengajuan/{pengajuan}/submit', [SiswaPengajuanController::class, 'submit'])->name('pengajuan.submit')
            ->middleware('permission:pengajuan.ajukan');
        Route::get('riwayat', [SiswaPengajuanController::class, 'riwayat'])->name('riwayat')
            ->middleware('permission:riwayat.lihat-sendiri');
    });

    Route::prefix('kaprog')->name('kaprog.')->group(function () {
        Route::inertia('dashboard', 'kaprog/dashboard')->name('dashboard');
        Route::get('verifikasi', [VerifikasiController::class, 'index'])->name('verifikasi.index')
            ->middleware('permission:pengajuan.lihat');
        Route::get('verifikasi/{pengajuan}', [VerifikasiController::class, 'show'])->name('verifikasi.show')
            ->middleware('permission:pengajuan.lihat');
        Route::post('verifikasi/{pengajuan}/approve', [VerifikasiController::class, 'approve'])->name('verifikasi.approve')
            ->middleware('permission:pengajuan.setujui');
        Route::post('verifikasi/{pengajuan}/reject', [VerifikasiController::class, 'reject'])->name('verifikasi.reject')
            ->middleware('permission:pengajuan.tolak');
    });

    Route::prefix('guru-pembimbing')->name('guru-pembimbing.')->group(function () {
        Route::inertia('dashboard', 'guru-pembimbing/dashboard')->name('dashboard');
    });

    Route::prefix('hubin')->name('hubin.')->group(function () {
        Route::inertia('dashboard', 'hubin/dashboard')->name('dashboard');

        Route::resource('perusahaan', PerusahaanController::class)
            ->only(['index', 'store', 'update', 'destroy'])
            ->middleware('permission:master-data.kelola');
        Route::resource('periode-pkl', PeriodePklController::class)
            ->only(['index', 'store', 'update', 'destroy'])
            ->middleware('permission:master-data.kelola');
        Route::resource('kompetensi-keahlian', KompetensiKeahlianController::class)
            ->only(['index', 'store', 'update', 'destroy'])
            ->middleware('permission:master-data.kelola');
        Route::resource('indikator-penilaian', IndikatorPenilaianController::class)
            ->only(['index', 'store', 'update', 'destroy'])
            ->middleware('permission:master-penilaian.kelola');

        Route::post('indikator-penilaian/{indikatorPenilaian}/section', [IndikatorPenilaianController::class, 'storeSection'])
            ->name('indikator-penilaian.section.store')->middleware('permission:master-penilaian.kelola');
        Route::put('indikator-penilaian/section/{section}', [IndikatorPenilaianController::class, 'updateSection'])
            ->name('indikator-penilaian.section.update')->middleware('permission:master-penilaian.kelola');
        Route::delete('indikator-penilaian/section/{section}', [IndikatorPenilaianController::class, 'destroySection'])
            ->name('indikator-penilaian.section.destroy')->middleware('permission:master-penilaian.kelola');

        Route::get('kelola-akun', [AkunController::class, 'index'])->name('kelola-akun.index')
            ->middleware('permission:akun.kelola');
        Route::patch('kelola-akun/{guruProfile}/nonaktifkan', [AkunController::class, 'nonaktifkanKaprog'])
            ->name('kelola-akun.nonaktifkan')->middleware('permission:akun.kelola');
        Route::post('kelola-akun/import-guru', [AkunController::class, 'importGuru'])
            ->name('kelola-akun.import.guru')->middleware('permission:akun.kelola');
        Route::post('kelola-akun/import-siswa', [AkunController::class, 'importSiswa'])
            ->name('kelola-akun.import.siswa')->middleware('permission:akun.kelola');
        Route::get('kelola-akun/export', [AkunController::class, 'export'])
            ->name('kelola-akun.export')->middleware('permission:akun.kelola');
        Route::get('kelola-akun/template-guru', [AkunController::class, 'templateGuru'])
            ->name('kelola-akun.template.guru');
        Route::get('kelola-akun/template-siswa', [AkunController::class, 'templateSiswa'])
            ->name('kelola-akun.template.siswa');

        Route::prefix('surat-pengantar')->name('surat-pengantar.')->middleware('permission:surat.terbitkan')->group(function () {
            Route::get('/', [SuratPengantarController::class, 'index'])->name('index');
            Route::get('{perusahaan}/{periode}', [SuratPengantarController::class, 'preview'])->name('preview');
            Route::get('{perusahaan}/{periode}/cetak', [SuratPengantarController::class, 'cetak'])->name('cetak');
            Route::post('{perusahaan}/{periode}/kirim', [SuratPengantarController::class, 'kirim'])->name('kirim');
            Route::get('download/{suratPengantar}', [SuratPengantarController::class, 'download'])->name('download');
            Route::post('{pengajuan}/tolak-balasan', [SuratPengantarController::class, 'tolakBalasan'])
                ->name('tolak-balasan')->middleware('permission:surat-balasan.verifikasi');
        });
    });

    Route::prefix('siswa')->name('siswa.')->group(function () {
        Route::post('pengajuan/{pengajuan}/surat-balasan', [SuratBalasanController::class, 'store'])
            ->name('surat-balasan.store')->middleware('permission:surat-balasan.upload');
        Route::get('pengajuan/{pengajuan}/surat-balasan', [SuratBalasanController::class, 'download'])
            ->name('surat-balasan.download')->middleware('permission:surat-balasan.upload');
    });
});

require __DIR__.'/settings.php';
