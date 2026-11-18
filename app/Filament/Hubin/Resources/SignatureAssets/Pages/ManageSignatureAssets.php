<?php

namespace App\Filament\Hubin\Resources\SignatureAssets\Pages;

use App\Filament\Hubin\Resources\SignatureAssets\SignatureAssetResource;
use Filament\Resources\Pages\ManageRecords;

class ManageSignatureAssets extends ManageRecords
{
    protected static string $resource = SignatureAssetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            SignatureAssetResource::uploadAction(),
        ];
    }
}
