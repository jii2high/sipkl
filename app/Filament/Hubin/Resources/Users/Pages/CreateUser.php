<?php

namespace App\Filament\Hubin\Resources\Users\Pages;

use App\Filament\Hubin\Resources\Users\UserResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    /**
     * Akun baru selalu memakai password awal NIS/NIP dan wajib melewati
     * alur login awal (OTP + ganti password).
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['password'] = $data['nis_nip'];
        $data['is_first_login'] = true;
        $data['email_verified_at'] = now();

        return $data;
    }
}
