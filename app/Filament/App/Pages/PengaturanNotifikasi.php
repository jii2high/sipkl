<?php

namespace App\Filament\App\Pages;

use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

/**
 * Preferensi push opt-in (BR-F14-1, T10.3). Halaman ini satu-satunya tempat
 * meminta jawaban; aplikasi tidak pernah menanyakan ulang secara otomatis.
 */
class PengaturanNotifikasi extends Page
{
    protected string $view = 'filament.app.pages.pengaturan-notifikasi';

    protected static ?string $navigationLabel = 'Notifikasi';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBell;

    protected static ?int $navigationSort = 90;

    public ?bool $pushOptIn = null;

    public function mount(): void
    {
        $this->pushOptIn = auth()->user()?->push_opt_in;
    }

    public function getTitle(): string
    {
        return __('pkl.push.judul');
    }

    public function getSubheading(): ?string
    {
        return __('pkl.push.subjudul');
    }

    public function setPush(bool $aktif): void
    {
        auth()->user()?->forceFill(['push_opt_in' => $aktif])->save();

        $this->pushOptIn = $aktif;

        Notification::make()
            ->title(__($aktif ? 'pkl.push.diaktifkan' : 'pkl.push.dimatikan'))
            ->success()
            ->send();
    }
}
