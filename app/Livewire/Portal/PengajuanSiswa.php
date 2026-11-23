<?php

namespace App\Livewire\Portal;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Portal siswa `/siswa` (R4.1): alur pengajuan PKL dari draf sampai keputusan.
 * Rangka rute dan layout ada lebih dulu; isi halaman menyusul di R4.1.
 */
#[Layout('components.layouts.portal')]
#[Title('Pengajuan PKL')]
class PengajuanSiswa extends Component
{
    public function render(): mixed
    {
        return view('livewire.portal.pengajuan-siswa');
    }
}