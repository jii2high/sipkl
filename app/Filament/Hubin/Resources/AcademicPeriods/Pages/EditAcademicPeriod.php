<?php

namespace App\Filament\Hubin\Resources\AcademicPeriods\Pages;

use App\Filament\Hubin\Resources\AcademicPeriods\AcademicPeriodResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAcademicPeriod extends EditRecord
{
    protected static string $resource = AcademicPeriodResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
