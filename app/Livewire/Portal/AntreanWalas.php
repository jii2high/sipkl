<?php

namespace App\Livewire\Portal;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Portal walas `/walas` (R4.4): antrean Gate 2 kelas yang diampu.
 */
#[Layout('components.layouts.portal')]
#[Title('Antrean kelas')]
class AntreanWalas extends Component
{
    public function render(): mixed
    {
        return view('livewire.portal.antrean-walas');
    }
}