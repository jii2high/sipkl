<?php

namespace App\Filament\Hubin\Resources\AcademicPeriods\Pages;

use App\Filament\Hubin\Resources\AcademicPeriods\AcademicPeriodResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageAcademicPeriods extends ManageRecords
{
    protected static string $resource = AcademicPeriodResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
