<?php

namespace App\Livewire\Portal;

use App\Models\JadwalSidang as JadwalSidangModel;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Portal siswa `/siswa/jadwal-sidang` (R4.2): hanya tanggal, penguji, dan status
 * jadwal milik siswa. Nilai sidang tidak pernah dikirim ke halaman ini (F9).
 */
#[Layout('components.layouts.portal')]
#[Title('Jadwal sidang')]
class JadwalSidang extends Component
{
    /** @return Collection<int, JadwalSidangModel> */
    public function schedules(): Collection
    {
        return JadwalSidangModel::query()
            ->with('externalExaminer')
            ->whereHas('pengajuanSiswa', fn ($pengajuan) => $pengajuan
                ->where('siswa_id', auth()->id()))
            ->orderBy('tanggal')
            ->orderBy('urutan')
            ->get();
    }

    public function statusLabel(string $status): string
    {
        return match ($status) {
            JadwalSidangModel::STATUS_TERJADWAL => 'Terjadwal',
            JadwalSidangModel::STATUS_AKTIF => 'Sedang berlangsung',
            JadwalSidangModel::STATUS_SELESAI => 'Selesai',
            default => $status,
        };
    }

    public function render(): mixed
    {
        return view('livewire.portal.jadwal-sidang');
    }
}
