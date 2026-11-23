<?php

namespace App\Filament\Hubin\Resources\FaqCategories;

use App\Filament\Hubin\Resources\FaqCategories\Pages\ManageFaqCategories;
use App\Models\FaqCategory;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Kategori FAQ panel Hubin (BR-F16-3). Kategori yang masih dipakai FAQ tidak
 * dapat dihapus supaya FAQ tidak kehilangan pengelompokannya.
 */
class FaqCategoryResource extends Resource
{
    protected static ?string $model = FaqCategory::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static ?string $modelLabel = 'kategori FAQ';

    protected static ?string $pluralModelLabel = 'kategori FAQ';

    protected static ?int $navigationSort = 11;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('nama')
                    ->label('Nama kategori')
                    ->required()
                    ->maxLength(100),
                TextInput::make('sort_order')
                    ->label('Urutan')
                    ->helperText('Angka kecil tampil lebih dulu di halaman publik.')
                    ->numeric()
                    ->integer()
                    ->minValue(0)
                    ->default(0)
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('nama')
                    ->label('Kategori')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('sort_order')
                    ->label('Urutan')
                    ->sortable(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->visible(fn (FaqCategory $record): bool => ! $record->faqs()->exists()),
            ])
            ->emptyStateHeading('Belum ada kategori')
            ->emptyStateDescription('Buat kategori sebelum menambah FAQ.');
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageFaqCategories::route('/'),
        ];
    }
}
