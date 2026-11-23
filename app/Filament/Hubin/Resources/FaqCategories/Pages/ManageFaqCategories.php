<?php

namespace App\Filament\Hubin\Resources\FaqCategories\Pages;

use App\Filament\Hubin\Resources\FaqCategories\FaqCategoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageFaqCategories extends ManageRecords
{
    protected static string $resource = FaqCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
