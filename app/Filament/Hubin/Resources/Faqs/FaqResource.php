<?php

namespace App\Filament\Hubin\Resources\Faqs;

use App\Filament\Hubin\Resources\Faqs\Pages\ManageFaqs;
use App\Models\Faq;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * CRUD FAQ panel Hubin (BR-F16-3). FAQ yang belum terbit tetap tersimpan untuk
 * draf, tetapi halaman publik hanya membaca `is_published = true`.
 */
class FaqResource extends Resource
{
    protected static ?string $model = Faq::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQuestionMarkCircle;

    protected static ?string $modelLabel = 'FAQ';

    protected static ?string $pluralModelLabel = 'FAQ';

    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Isi FAQ')
                    ->schema([
                        Select::make('faq_category_id')
                            ->label('Kategori')
                            ->relationship('category', 'nama')
                            ->required(),
                        TextInput::make('pertanyaan')
                            ->label('Pertanyaan')
                            ->required()
                            ->maxLength(500)
                            ->columnSpanFull(),
                        Textarea::make('jawaban')
                            ->label('Jawaban')
                            ->required()
                            ->rows(6)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Section::make('Publikasi')
                    ->schema([
                        TextInput::make('sort_order')
                            ->label('Urutan')
                            ->helperText('Angka kecil tampil lebih dulu di dalam kategori.')
                            ->numeric()
                            ->integer()
                            ->minValue(0)
                            ->default(0)
                            ->required(),
                        Toggle::make('is_published')
                            ->label('Terbitkan di halaman publik')
                            ->default(true),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('faq_category_id')
            ->columns([
                TextColumn::make('pertanyaan')
                    ->label('Pertanyaan')
                    ->searchable()
                    ->wrap(),
                TextColumn::make('category.nama')
                    ->label('Kategori'),
                TextColumn::make('sort_order')
                    ->label('Urutan')
                    ->sortable(),
                IconColumn::make('is_published')
                    ->label('Terbit')
                    ->boolean(),
                TextColumn::make('updated_at')
                    ->label('Diperbarui')
                    ->since()
                    ->sortable(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Belum ada FAQ')
            ->emptyStateDescription('Tambahkan pertanyaan pertama agar halaman publik terisi.');
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageFaqs::route('/'),
        ];
    }
}
