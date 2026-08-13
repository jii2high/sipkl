<?php

use App\Http\Controllers\Auth\FirstLoginController;
use App\Http\Controllers\Auth\PasswordOtpController;
use App\Http\Controllers\GuruPembimbing\FormulirPenilaianController;
use App\Http\Controllers\GuruPembimbing\KunjunganController;
use App\Http\Controllers\Hubin\IndikatorPenilaianController;
use App\Http\Controllers\Hubin\KelolaSiswaController;
use App\Http\Controllers\Hubin\KelolaStaffController;
use App\Http\Controllers\Hubin\KompetensiKeahlianController;
use App\Http\Controllers\Hubin\PeriodePklController;
use App\Http\Controllers\Hubin\PerusahaanController;
use App\Http\Controllers\Hubin\RecycleBinController;
use App\Http\Controllers\Hubin\SuratPengantarController;
use App\Http\Controllers\Hubin\SuratTugasController;
use App\Http\Controllers\Kaprog\IndikatorJurusanController;
use App\Http\Controllers\Kaprog\MonitoringController;
use App\Http\Controllers\Kaprog\PenugasanController;
use App\Http\Controllers\Kaprog\RiwayatVerifikasiController;
use App\Http\Controllers\Kaprog\SuratTugasRequestController;
use App\Http\Controllers\Kaprog\VerifikasiController;
use App\Http\Controllers\Siswa\LaporanAkhirController;
use App\Http\Controllers\Siswa\PengajuanController as SiswaPengajuanController;
use App\Http\Controllers\Siswa\SuratBalasanController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth'])->group(function () {
    Route::get('auth/force-change-password', [FirstLoginController::class, 'create'])->name('password.force-change');
    Route::post('auth/force-change-password', [FirstLoginController::class, 'store'])
        ->middleware(['auth', 'otp.verified'])
        ->name('password.force-change.update');
    Route::post('/password/otp/request', [PasswordOtpController::class, 'request'])
        ->name('password.otp.request');
    Route::post('/password/otp/verify', [PasswordOtpController::class, 'verify'])
        ->name('password.otp.verify');
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

        Route::get('penugasan', [PenugasanController::class, 'index'])->name('penugasan.index')
            ->middleware('permission:penugasan.kelola');
        Route::post('penugasan/{pengajuan}', [PenugasanController::class, 'store'])->name('penugasan.store')
            ->middleware('permission:penugasan.kelola');
        Route::post('surat-tugas/request', [SuratTugasRequestController::class, 'store'])->name('surat-tugas.request')
            ->middleware('permission:penugasan.kelola');

        // Riwayat verifikasi — tanpa tombol aksi (BR4)
        Route::get('riwayat-verifikasi', [RiwayatVerifikasiController::class, 'index'])
            ->name('riwayat-verifikasi.index')->middleware('permission:pengajuan.lihat');

        // Indikator penilaian jurusan — EXCLUSIVE Kaprog (BR10-style)
        Route::prefix('indikator-jurusan')->name('indikator-jurusan.')->middleware('permission:master-penilaian.kelola')->group(function () {
            Route::get('/', [IndikatorJurusanController::class, 'index'])->name('index');
            Route::post('/', [IndikatorJurusanController::class, 'store'])->name('store');
            Route::put('{indikatorPenilaian}', [IndikatorJurusanController::class, 'update'])->name('update');
            Route::delete('{indikatorPenilaian}', [IndikatorJurusanController::class, 'destroy'])->name('destroy');
            Route::post('{indikatorPenilaian}/section', [IndikatorJurusanController::class, 'storeSection'])->name('section.store');
            Route::put('section/{section}', [IndikatorJurusanController::class, 'updateSection'])->name('section.update');
            Route::delete('section/{section}', [IndikatorJurusanController::class, 'destroySection'])->name('section.destroy');
            Route::post('section/{section}/sub-section', [IndikatorJurusanController::class, 'storeSubSection'])->name('sub-section.store');
            Route::put('sub-section/{subSection}', [IndikatorJurusanController::class, 'updateSubSection'])->name('sub-section.update');
            Route::delete('sub-section/{subSection}', [IndikatorJurusanController::class, 'destroySubSection'])->name('sub-section.destroy');
            Route::post('reorder', [IndikatorJurusanController::class, 'reorder'])->name('reorder');
        });

        // Monitoring bulanan — Kaprog hanya buat draft, Hubin yang terbitkan (sesuai notes diskusi)
        Route::prefix('monitoring')->name('monitoring.')->middleware('permission:surat-tugas.kelola')->group(function () {
            Route::get('/', [MonitoringController::class, 'index'])->name('index');
            Route::post('/', [MonitoringController::class, 'store'])->name('store');
            Route::delete('{suratTugas}', [MonitoringController::class, 'destroy'])->name('destroy');
            // Route terbit sengaja DIHAPUS — Kaprog tidak bisa terbitkan sendiri (lihat AGENTS.md)
        });
    });

    Route::prefix('guru-pembimbing')->name('guru-pembimbing.')->group(function () {
        Route::inertia('dashboard', 'guru-pembimbing/dashboard')->name('dashboard');
        Route::get('kunjungan', [KunjunganController::class, 'index'])->name('kunjungan.index')
            ->middleware('permission:kunjungan.lihat');
        Route::post('kunjungan/{penugasan}', [KunjunganController::class, 'store'])->name('kunjungan.store')
            ->middleware('permission:kunjungan.isi');

        Route::get('penilaian', [FormulirPenilaianController::class, 'index'])->name('penilaian.index')
            ->middleware('permission:penilaian.lihat');
        Route::get('penilaian/{penugasan}', [FormulirPenilaianController::class, 'create'])->name('penilaian.create')
            ->middleware('permission:penilaian.isi');
        Route::post('penilaian/{penugasan}', [FormulirPenilaianController::class, 'store'])->name('penilaian.store')
            ->middleware('permission:penilaian.isi');
    });

    Route::prefix('hubin')->name('hubin.')->group(function () {
        Route::inertia('dashboard', 'hubin/dashboard')->name('dashboard');

        Route::get('perusahaan', [PerusahaanController::class, 'index'])
            ->name('perusahaan.index')
            ->middleware('permission:master-data.kelola');
        Route::post('perusahaan', [PerusahaanController::class, 'store'])
            ->name('perusahaan.store')
            ->middleware('permission:master-data.kelola');
        Route::put('perusahaan/{perusahaan}', [PerusahaanController::class, 'update'])
            ->name('perusahaan.update')
            ->middleware('permission:master-data.kelola');
        Route::delete('perusahaan/{perusahaan}', [PerusahaanController::class, 'destroy'])
            ->name('perusahaan.destroy')
            ->middleware('permission:master-data.kelola');

        Route::get('periode-pkl', [PeriodePklController::class, 'index'])
            ->name('periode-pkl.index')
            ->middleware('permission:master-data.kelola');
        Route::post('periode-pkl', [PeriodePklController::class, 'store'])
            ->name('periode-pkl.store')
            ->middleware('permission:master-data.kelola');
        Route::put('periode-pkl/{periodePkl}', [PeriodePklController::class, 'update'])
            ->name('periode-pkl.update')
            ->middleware('permission:master-data.kelola');
        Route::delete('periode-pkl/{periodePkl}', [PeriodePklController::class, 'destroy'])
            ->name('periode-pkl.destroy')
            ->middleware('permission:master-data.kelola');

        Route::get('kompetensi-keahlian', [KompetensiKeahlianController::class, 'index'])
            ->name('kompetensi-keahlian.index')
            ->middleware('permission:master-data.kelola');
        Route::post('kompetensi-keahlian', [KompetensiKeahlianController::class, 'store'])
            ->name('kompetensi-keahlian.store')
            ->middleware('permission:master-data.kelola');
        Route::put('kompetensi-keahlian/{kompetensiKeahlian}', [KompetensiKeahlianController::class, 'update'])
            ->name('kompetensi-keahlian.update')
            ->middleware('permission:master-data.kelola');
        Route::delete('kompetensi-keahlian/{kompetensiKeahlian}', [KompetensiKeahlianController::class, 'destroy'])
            ->name('kompetensi-keahlian.destroy')
            ->middleware('permission:master-data.kelola');

        Route::get('indikator-penilaian', [IndikatorPenilaianController::class, 'index'])
            ->name('indikator-penilaian.index')
            ->middleware('permission:master-penilaian.kelola');
        Route::post('indikator-penilaian', [IndikatorPenilaianController::class, 'store'])
            ->name('indikator-penilaian.store')
            ->middleware('permission:master-penilaian.kelola');
        Route::put('indikator-penilaian/{indikatorPenilaian}', [IndikatorPenilaianController::class, 'update'])
            ->name('indikator-penilaian.update')
            ->middleware('permission:master-penilaian.kelola');
        Route::delete('indikator-penilaian/{indikatorPenilaian}', [IndikatorPenilaianController::class, 'destroy'])
            ->name('indikator-penilaian.destroy')
            ->middleware('permission:master-penilaian.kelola');

        Route::post('indikator-penilaian/{indikatorPenilaian}/section', [IndikatorPenilaianController::class, 'storeSection'])
            ->name('indikator-penilaian.section.store')->middleware('permission:master-penilaian.kelola');
        Route::put('indikator-penilaian/section/{section}', [IndikatorPenilaianController::class, 'updateSection'])
            ->name('indikator-penilaian.section.update')->middleware('permission:master-penilaian.kelola');
        Route::delete('indikator-penilaian/section/{section}', [IndikatorPenilaianController::class, 'destroySection'])
            ->name('indikator-penilaian.section.destroy')->middleware('permission:master-penilaian.kelola');
        Route::post('indikator-penilaian/section/{section}/sub-section', [IndikatorPenilaianController::class, 'storeSubSection'])
            ->name('indikator-penilaian.sub-section.store')->middleware('permission:master-penilaian.kelola');
        Route::put('indikator-penilaian/sub-section/{subSection}', [IndikatorPenilaianController::class, 'updateSubSection'])
            ->name('indikator-penilaian.sub-section.update')->middleware('permission:master-penilaian.kelola');
        Route::delete('indikator-penilaian/sub-section/{subSection}', [IndikatorPenilaianController::class, 'destroySubSection'])
            ->name('indikator-penilaian.sub-section.destroy')->middleware('permission:master-penilaian.kelola');
        Route::post('indikator-penilaian/reorder', [IndikatorPenilaianController::class, 'reorder'])
            ->name('indikator-penilaian.reorder')->middleware('permission:master-penilaian.kelola');

        Route::get('kelola-siswa', [KelolaSiswaController::class, 'index'])->name('kelola-siswa.index')
            ->middleware('permission:akun.kelola');
        Route::post('kelola-siswa', [KelolaSiswaController::class, 'store'])
            ->name('kelola-siswa.store')->middleware('permission:akun.kelola');
        Route::put('kelola-siswa/{user}', [KelolaSiswaController::class, 'update'])
            ->name('kelola-siswa.update')->middleware('permission:akun.kelola');
        Route::delete('kelola-siswa/{user}', [KelolaSiswaController::class, 'destroy'])
            ->name('kelola-siswa.destroy')->middleware('permission:akun.kelola');
        Route::post('kelola-siswa/import', [KelolaSiswaController::class, 'import'])
            ->name('kelola-siswa.import')->middleware('permission:akun.kelola');
        Route::get('kelola-siswa/export', [KelolaSiswaController::class, 'export'])
            ->name('kelola-siswa.export')->middleware('permission:akun.kelola');
        Route::get('kelola-siswa/template', [KelolaSiswaController::class, 'template'])
            ->name('kelola-siswa.template');

        Route::get('kelola-staff', [KelolaStaffController::class, 'index'])->name('kelola-staff.index')
            ->middleware('permission:akun.kelola');
        Route::post('kelola-staff', [KelolaStaffController::class, 'store'])
            ->name('kelola-staff.store')->middleware('permission:akun.kelola');
        Route::put('kelola-staff/{guruProfile}', [KelolaStaffController::class, 'update'])
            ->name('kelola-staff.update')->middleware('permission:akun.kelola');
        Route::delete('kelola-staff/{guruProfile}', [KelolaStaffController::class, 'destroy'])
            ->name('kelola-staff.destroy')->middleware('permission:akun.kelola');
        Route::patch('kelola-staff/{guruProfile}/nonaktifkan', [KelolaStaffController::class, 'nonaktifkanKaprog'])
            ->name('kelola-staff.nonaktifkan')->middleware('permission:akun.kelola');
        Route::post('kelola-staff/import', [KelolaStaffController::class, 'import'])
            ->name('kelola-staff.import')->middleware('permission:akun.kelola');
        Route::get('kelola-staff/template', [KelolaStaffController::class, 'template'])
            ->name('kelola-staff.template');

        Route::get('recycle-bin', [RecycleBinController::class, 'index'])->name('recycle-bin.index')
            ->middleware('permission:akun.kelola');
        Route::post('recycle-bin/restore', [RecycleBinController::class, 'restore'])
            ->name('recycle-bin.restore')->middleware('permission:akun.kelola');
        Route::delete('recycle-bin', [RecycleBinController::class, 'forceDestroy'])
            ->name('recycle-bin.force-destroy')->middleware('permission:akun.kelola');

        Route::prefix('surat-pengantar')->name('surat-pengantar.')->middleware('permission:surat.terbitkan')->group(function () {
            Route::get('/', [SuratPengantarController::class, 'index'])->name('index');
            Route::get('{perusahaan}/{kompetensiKeahlian}/{periode}', [SuratPengantarController::class, 'preview'])->name('preview');
            Route::get('{perusahaan}/{kompetensiKeahlian}/{periode}/cetak', [SuratPengantarController::class, 'cetak'])->name('cetak');
            Route::post('{perusahaan}/{kompetensiKeahlian}/{periode}/kirim', [SuratPengantarController::class, 'kirim'])->name('kirim');
            Route::get('download/{suratPengantar}', [SuratPengantarController::class, 'download'])->name('download');
            Route::post('{pengajuan}/tolak-balasan', [SuratPengantarController::class, 'tolakBalasan'])
                ->name('tolak-balasan')->middleware('permission:surat-balasan.verifikasi');
        });

        Route::prefix('surat-tugas')->name('surat-tugas.')->middleware('permission:surat-tugas.kelola')->group(function () {
            Route::get('/', [SuratTugasController::class, 'index'])->name('index');
            Route::get('antrian', [SuratTugasController::class, 'antrian'])->name('antrian');
            Route::post('/', [SuratTugasController::class, 'store'])->name('store');
            Route::post('{suratTugas}/terbit', [SuratTugasController::class, 'terbit'])->name('terbit');
            Route::post('{suratTugas}/nonaktif', [SuratTugasController::class, 'nonaktif'])->name('nonaktif');
            Route::delete('{suratTugas}/tolak', [SuratTugasController::class, 'tolak'])->name('tolak');
            Route::get('{suratTugas}/download', [SuratTugasController::class, 'download'])->name('download');
        });
    });

    Route::prefix('siswa')->name('siswa.')->group(function () {
        Route::post('pengajuan/{pengajuan}/surat-balasan', [SuratBalasanController::class, 'store'])
            ->name('surat-balasan.store')->middleware('permission:surat-balasan.upload');
        Route::get('pengajuan/{pengajuan}/surat-balasan', [SuratBalasanController::class, 'download'])
            ->name('surat-balasan.download')->middleware('permission:surat-balasan.upload');
        Route::post('pengajuan/{pengajuan}/laporan-akhir', [LaporanAkhirController::class, 'store'])
            ->name('laporan-akhir.store')->middleware('permission:laporan-akhir.upload');
        Route::get('pengajuan/{pengajuan}/laporan-akhir', [LaporanAkhirController::class, 'download'])
            ->name('laporan-akhir.download')->middleware('permission:laporan-akhir.upload');
    });
});

require __DIR__.'/settings.php';
