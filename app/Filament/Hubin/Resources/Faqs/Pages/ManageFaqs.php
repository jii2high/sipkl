<?php

namespace App\Filament\Hubin\Resources\Faqs\Pages;

use App\Filament\Hubin\Resources\Faqs\FaqResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageFaqs extends ManageRecords
{
    protected static string $resource = FaqResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->mutateDataUsing(function (array $data): array {
                    $data['created_by'] = auth()->id();

                    return $data;
                }),
        ];
    }
}
