<?php

namespace App\Filament\Hubin\Resources\AcademicPeriods;

use App\Filament\Hubin\Resources\AcademicPeriods\Pages\CreateAcademicPeriod;
use App\Filament\Hubin\Resources\AcademicPeriods\Pages\EditAcademicPeriod;
use App\Filament\Hubin\Resources\AcademicPeriods\Pages\ListAcademicPeriods;
use App\Filament\Hubin\Resources\AcademicPeriods\Schemas\AcademicPeriodForm;
use App\Filament\Hubin\Resources\AcademicPeriods\Tables\AcademicPeriodsTable;
use App\Models\AcademicPeriod;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class AcademicPeriodResource extends Resource
{
    protected static ?string $model = AcademicPeriod::class;

    protected static ?string $recordTitleAttribute = 'tahun_ajaran';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static string|UnitEnum|null $navigationGroup = 'Master Data';

    protected static ?string $navigationLabel = 'Periode Akademik';

    protected static ?string $modelLabel = 'periode akademik';

    protected static ?string $pluralModelLabel = 'periode akademik';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return AcademicPeriodForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AcademicPeriodsTable::configure($table);
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
            'index' => ListAcademicPeriods::route('/'),
            'create' => CreateAcademicPeriod::route('/create'),
            'edit' => EditAcademicPeriod::route('/{record}/edit'),
        ];
    }
}
