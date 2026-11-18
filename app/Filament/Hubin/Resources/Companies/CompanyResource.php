<?php

namespace App\Filament\Hubin\Resources\Companies;

use App\Filament\Hubin\Resources\Companies\Pages\CreateCompany;
use App\Filament\Hubin\Resources\Companies\Pages\EditCompany;
use App\Filament\Hubin\Resources\Companies\Pages\ListCompanies;
use App\Filament\Hubin\Resources\Companies\Schemas\CompanyForm;
use App\Filament\Hubin\Resources\Companies\Tables\CompaniesTable;
use App\Models\Company;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class CompanyResource extends Resource
{
    protected static ?string $model = Company::class;

    protected static ?string $recordTitleAttribute = 'nama';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static string|UnitEnum|null $navigationGroup = 'Master Data';

    protected static ?string $navigationLabel = 'Perusahaan';

    protected static ?string $modelLabel = 'perusahaan';

    protected static ?string $pluralModelLabel = 'perusahaan';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return CompanyForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CompaniesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCompanies::route('/'),
            'create' => CreateCompany::route('/create'),
            'edit' => EditCompany::route('/{record}/edit'),
        ];
    }
}
