<?php

namespace App\Filament\App\Pages;

use App\Enums\PengajuanStatus;
use App\Enums\Role;
use App\Models\PengajuanSiswa;
use App\Support\JakartaTime;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;

/**
 * Kanban read-only Walas (BR-F15-1, Q-K). Lima kolom mengikuti posisi pengajuan;
 * query dibatasi `visibleTo()` ke kelas peran aktif, jadi kelas lain tidak pernah
 * dimuat. Tidak ada aksi ubah di halaman ini.
 */
class KanbanKelas extends Page
{
    protected string $view = 'filament.app.pages.kanban-kelas';

    protected static ?string $navigationLabel = 'Kanban kelas';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedViewColumns;

    protected static ?int $navigationSort = 3;

    public static function canAccess(): bool
    {
        return session('active_role') === Role::Walas->value;
    }

    public function getTitle(): string
    {
        return __('pkl.monitor.kanban_judul');
    }

    public function getSubheading(): ?string
    {
        return __('pkl.monitor.kanban_petunjuk');
    }

    /**
     * @return array<int, array{judul: string, items: Collection<int, PengajuanSiswa>}>
     */
    public function board(): array
    {
        $columns = [];

        foreach (__('pkl.monitor.kolom') as $number => $title) {
            $columns[$number] = ['judul' => $title, 'items' => collect()];
        }

        $pengajuan = PengajuanSiswa::query()
            ->visibleTo(auth()->user(), Role::Walas->value)
            ->whereNotIn('status', [PengajuanStatus::Rejected->value, PengajuanStatus::Withdrawn->value])
            ->with(['siswa:id,name', 'company:id,nama', 'laporanVersions'])
            ->get();

        foreach ($pengajuan as $row) {
            $columns[$this->columnFor($row)]['items']->push($row);
        }

        return $columns;
    }

    /** Pengajuan yang sudah selesai di luar alur tidak punya kolom kanban. */
    public function hiddenCount(): int
    {
        return PengajuanSiswa::query()
            ->visibleTo(auth()->user(), Role::Walas->value)
            ->whereIn('status', [PengajuanStatus::Rejected->value, PengajuanStatus::Withdrawn->value])
            ->count();
    }

    public function waitingDays(PengajuanSiswa $pengajuan): int
    {
        return JakartaTime::workingDaysElapsed($pengajuan->updated_at);
    }

    /** Q-K: posisi pengajuan, lalu versi laporan terbaru untuk kolom 3-5. */
    private function columnFor(PengajuanSiswa $pengajuan): int
    {
        return match ($pengajuan->status) {
            PengajuanStatus::MenungguSuratPengantar->value,
            PengajuanStatus::SuratPengantarTerbit->value,
            PengajuanStatus::MenungguBalasanPerusahaan->value => 2,
            PengajuanStatus::SedangPkl->value => $this->pklColumn($pengajuan),
            default => 1,
        };
    }

    private function pklColumn(PengajuanSiswa $pengajuan): int
    {
        $report = $pengajuan->laporanVersions->sortByDesc('version_number')->first();

        return match (true) {
            $report === null => 3,
            $report->is_legit => 5,
            default => 4,
        };
    }
}
