<?php

namespace App\Livewire\Portal;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Portal kepsek `/kepsek` (R4.3): tanda tangan laporan step 5.
 */
#[Layout('components.layouts.portal')]
#[Title('Pengesahan laporan')]
class PengesahanKepsek extends Component
{
    public function render(): mixed
    {
        return view('livewire.portal.pengesahan-kepsek');
    }
}