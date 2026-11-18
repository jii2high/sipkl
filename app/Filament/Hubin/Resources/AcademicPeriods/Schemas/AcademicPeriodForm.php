<?php

namespace App\Filament\Hubin\Resources\AcademicPeriods\Schemas;

use App\Models\AcademicPeriod;
use Closure;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class AcademicPeriodForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identitas periode')
                    ->components([
                        TextInput::make('tahun_ajaran')
                            ->label('Tahun ajaran')
                            ->placeholder('2026/2027')
                            ->required()
                            ->regex('/^\d{4}\/\d{4}$/')
                            ->maxLength(9)
                            ->rule(function (Get $get, ?AcademicPeriod $record): Closure {
                                return function (string $attribute, mixed $value, Closure $fail) use ($get, $record): void {
                                    $batch = $get('batch');

                                    if (blank($value) || blank($batch)) {
                                        return;
                                    }

                                    $sudahAda = AcademicPeriod::query()
                                        ->where('tahun_ajaran', $value)
                                        ->where('batch', (string) $batch)
                                        ->when(
                                            $record !== null,
                                            fn ($query) => $query->whereKeyNot($record->getKey()),
                                        )
                                        ->exists();

                                    if ($sudahAda) {
                                        $fail('Tahun ajaran dan batch ini sudah terdaftar.');
                                    }
                                };
                            }),

                        Select::make('batch')
                            ->label('Batch')
                            ->options([
                                '1' => 'Batch 1',
                                '2' => 'Batch 2',
                            ])
                            ->default('1')
                            ->required()
                            ->selectablePlaceholder(false),

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
                    ->components([
                        Toggle::make('is_active')
                            ->label('Periode aktif')
                            ->helperText('Boleh lebih dari satu periode aktif.')
                            ->default(true),

                        Toggle::make('locked_for_new_submission')
                            ->label('Kunci pengajuan baru')
                            ->helperText('Jika aktif, siswa tidak bisa membuat pengajuan baru pada periode ini.')
                            ->default(false),
                    ])
                    ->columns(2),
            ]);
    }
}
