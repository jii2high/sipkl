<?php

namespace App\Filament\Hubin\Resources\Users\Pages;

use App\Actions\Master\ImportData;
use App\Filament\Hubin\Resources\Users\UserResource;
use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageUsers extends ManageRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Kata sandi awal = NIS/NIP (T2.2); pengguna wajib menggantinya saat login pertama.
            CreateAction::make()
                ->mutateDataUsing(fn (array $data): array => [
                    ...$data,
                    'password' => $data['nis_nip'] ?? null,
                    'is_first_login' => true,
                ]),
            UserResource::importAction(ImportData::KIND_ACCOUNTS, User::class),
        ];
    }
}
