<?php

namespace App\Filament\Hubin\Resources\Companies\Pages;

use App\Actions\Master\ImportData;
use App\Filament\Hubin\Resources\Companies\CompanyResource;
use App\Models\Company;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageCompanies extends ManageRecords
{
    protected static string $resource = CompanyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            CompanyResource::importAction(ImportData::KIND_COMPANIES, Company::class),
        ];
    }
}
