<?php

namespace App\Livewire\Eksternal;

use App\Enums\Role;
use App\Models\User;
use App\Notifications\Notification;
use App\Services\NotificationService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Halaman "Minta tautan baru" (BR-F11-4). Respons selalu netral dan tidak pernah
 * memeriksa apakah email terdaftar; permintaan diteruskan ke Hubin.
 */
#[Layout('components.layouts.eksternal')]
#[Title('Minta tautan baru')]
class RequestNewLink extends Component
{
    public string $email = '';

    public bool $submitted = false;

    /**
     * @return array<string, array<int, string>>
     */
    protected function rules(): array
    {
        return ['email' => ['required', 'email', 'max:255']];
    }

    public function submit(NotificationService $notifications): void
    {
        $this->validate();

        $recipients = User::query()
            ->whereHas('roles', fn ($query) => $query->where('role', Role::Hubin->value))
            ->get();

        foreach ($recipients as $hubin) {
            $notifications->send(
                $hubin,
                new Notification(
                    'Permintaan tautan eksternal',
                    'Ada permintaan tautan akses baru dari '.$this->email.'. Tinjau dan terbitkan ulang dari panel Hubin.',
                    url('/hubin'),
                ),
            );
        }

        $this->reset('email');
        $this->submitted = true;
    }

    public function render(): mixed
    {
        return view('livewire.eksternal.request-new-link');
    }
}
