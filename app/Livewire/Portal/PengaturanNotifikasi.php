<?php

namespace App\Livewire\Portal;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Preferensi push opt-in (BR-F14-1, T10.3) untuk semua peran berakun, satu rute
 * `/notifikasi` (§5 no. 3). Ini satu-satunya tempat aplikasi meminta jawaban;
 * push tidak pernah ditanyakan ulang secara otomatis.
 */
#[Layout('components.layouts.portal')]
#[Title('Notifikasi')]
class PengaturanNotifikasi extends Component
{
    public ?bool $pushOptIn = null;

    public ?string $flash = null;

    public function mount(): void
    {
        $this->pushOptIn = auth()->user()?->push_opt_in;
    }

    public function setPush(bool $aktif): void
    {
        auth()->user()?->forceFill(['push_opt_in' => $aktif])->save();

        $this->pushOptIn = $aktif;
        $this->flash = __($aktif ? 'pkl.push.diaktifkan' : 'pkl.push.dimatikan');
    }

    public function render(): mixed
    {
        return view('livewire.portal.pengaturan-notifikasi');
    }
}
