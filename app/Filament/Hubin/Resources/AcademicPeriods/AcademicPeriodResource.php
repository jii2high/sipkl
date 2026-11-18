<?php

namespace App\Filament\Hubin\Resources\AcademicPeriods;

use App\Filament\Hubin\Resources\AcademicPeriods\Pages\ManageAcademicPeriods;
use App\Models\AcademicPeriod;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
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
 * Resource periode akademik panel Hubin (T2.2). Menyalakan/mematikan kunci pengajuan
 * baru dan mengaktifkan periode; penghapusan periode yang sudah punya pengajuan
 * ditolak `AcademicPeriodPolicy` (PRD F2 edge case), bukan oleh UI.
 */
class AcademicPeriodResource extends Resource
{
    protected static ?string $model = AcademicPeriod::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static ?string $modelLabel = 'periode akademik';

    protected static ?string $pluralModelLabel = 'periode akademik';

    protected static ?string $recordTitleAttribute = 'tahun_ajaran';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Periode')
                    ->schema([
                        TextInput::make('tahun_ajaran')
                            ->label('Tahun ajaran')
                            ->placeholder('2025/2026')
                            ->required()
                            ->maxLength(9)
                            ->rule('regex:/^[0-9]{4}\/[0-9]{4}$/')
                            ->validationMessages(['regex' => __('pkl.period.tahun_ajaran_format')]),
                        Select::make('batch')
                            ->label('Batch')
                            ->options(['1' => '1', '2' => '2'])
                            ->required(),
                        DatePicker::make('tanggal_mulai')
                            ->label('Tanggal mulai')
                            ->required(),
                        DatePicker::make('tanggal_selesai')
                            ->label('Tanggal selesai')
                            ->required()
                            ->afterOrEqual('tanggal_mulai'),
                    ])
                    ->columns(2),
                Section::make('Status')
                    ->schema([
                        Toggle::make('is_active')
                            ->label('Periode aktif')
                            ->default(true),
                        Toggle::make('locked_for_new_submission')
                            ->label('Kunci pengajuan baru')
                            ->helperText('Bila aktif, siswa tidak dapat mengirim pengajuan baru pada periode ini.'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('tahun_ajaran', 'desc')
            ->columns([
                TextColumn::make('tahun_ajaran')
                    ->label('Tahun ajaran')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('batch')
                    ->label('Batch')
                    ->badge(),
                TextColumn::make('tanggal_mulai')
                    ->label('Mulai')
                    ->date('d M Y')
                    ->sortable(),
                TextColumn::make('tanggal_selesai')
                    ->label('Selesai')
                    ->date('d M Y')
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
                IconColumn::make('locked_for_new_submission')
                    ->label('Terkunci')
                    ->boolean(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageAcademicPeriods::route('/'),
        ];
    }
}
