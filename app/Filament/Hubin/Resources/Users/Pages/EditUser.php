<?php

namespace App\Filament\Hubin\Resources\Users\Pages;

use App\Filament\Hubin\Resources\Users\UserResource;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;
}
