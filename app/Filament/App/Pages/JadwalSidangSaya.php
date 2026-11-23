<?php

namespace App\Filament\App\Pages;

use App\Enums\Role;
use App\Models\JadwalSidang;
use Filament\Pages\Page;
use Illuminate\Support\Collection;

/**
 * Jadwal sidang milik siswa (F9). Hanya tanggal, penguji, dan status jadwal yang
 * tampil; nilai tidak pernah dikirim ke halaman siswa.
 */
class JadwalSidangSaya extends Page
{
    protected string $view = 'filament.app.pages.jadwal-sidang-saya';

    protected static ?string $navigationLabel = 'Jadwal sidang';

    protected static ?string $title = 'Jadwal Sidang Saya';

    protected static ?int $navigationSort = 9;

    public static function canAccess(): bool
    {
        return session('active_role') === Role::Siswa->value;
    }

    /** @return Collection<int, JadwalSidang> */
    public function schedules()
    {
        return JadwalSidang::query()
            ->with('externalExaminer')
            ->whereHas('pengajuanSiswa', fn ($pengajuan) => $pengajuan
                ->where('siswa_id', auth()->user()->getKey()))
            ->orderBy('tanggal')
            ->orderBy('urutan')
            ->get();
    }
}
