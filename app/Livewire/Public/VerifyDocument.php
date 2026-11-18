<?php

namespace App\Livewire\Public;

use App\Services\DocumentService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Halaman verifikasi publik `/verify/{token}` (BR-F12-3). Hanya tiga field yang
 * ditampilkan: nomor surat, status, tanggal terbit. Token tidak dikenal atau
 * dicabut menghasilkan pesan netral yang sama.
 */
#[Layout('components.layouts.public')]
#[Title('Verifikasi surat')]
class VerifyDocument extends Component
{
    public string $token = '';

    public function mount(string $token): void
    {
        $this->token = $token;
    }

    public function render()
    {
        return view('livewire.public.verify-document', [
            'hasil' => app(DocumentService::class)->verify($this->token),
        ]);
    }
}
