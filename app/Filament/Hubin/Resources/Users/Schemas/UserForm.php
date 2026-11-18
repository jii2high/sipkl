<?php

namespace App\Filament\Hubin\Resources\Users\Schemas;

use App\Models\Jurusan;
use App\Models\Kelas;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identitas akun')
                    ->components([
                        TextInput::make('nis_nip')
                            ->label('NIS/NIP')
                            ->required()
                            ->maxLength(30)
                            ->unique(ignoreRecord: true),

                        TextInput::make('name')
                            ->label('Nama lengkap')
                            ->required()
                            ->maxLength(150),

                        TextInput::make('email')
                            ->label('Email sekolah')
                            ->email()
                            ->required()
                            ->maxLength(150)
                            ->unique(ignoreRecord: true)
                            ->rule('ends_with:@smkn1kotabekasi.sch.id')
                            ->helperText('Wajib memakai domain @smkn1kotabekasi.sch.id.'),

                        Select::make('user_type')
                            ->label('Tipe pengguna')
                            ->options([
                                'siswa' => 'Siswa',
                                'guru' => 'Guru',
                                'hubin' => 'Hubin',
                                'kepsek' => 'Kepala Sekolah',
                            ])
                            ->required()
                            ->live(),
                    ])
                    ->columns(2),

                Section::make('Scope sekolah')
                    ->description('Siswa wajib punya jurusan dan kelas. Guru/Hubin boleh kosong.')
                    ->components([
                        Select::make('jurusan_id')
                            ->label('Jurusan')
                            ->options(fn (): array => Jurusan::query()->orderBy('nama')->pluck('nama', 'id')->all())
                            ->searchable()
                            ->required(fn (Get $get): bool => $get('user_type') === 'siswa'),

                        Select::make('kelas_id')
                            ->label('Kelas')
                            ->options(fn (): array => Kelas::query()->orderBy('nama')->pluck('nama', 'id')->all())
                            ->searchable()
                            ->required(fn (Get $get): bool => $get('user_type') === 'siswa'),
                    ])
                    ->columns(2),

                Section::make('Status akun')
                    ->components([
                        Toggle::make('is_active')
                            ->label('Akun aktif')
                            ->default(true),
                    ]),
            ]);
    }
}
