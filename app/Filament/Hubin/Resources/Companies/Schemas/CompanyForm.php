<?php

namespace App\Filament\Hubin\Resources\Companies\Schemas;

use App\Models\Company;
use App\Rules\CompanyUniqueStrict;
use App\Rules\SignerNameIndividual;
use App\Services\CompanyNormalizer;
use App\Services\CompanyResolver;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class CompanyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identitas perusahaan')
                    ->components([
                        TextInput::make('nama')
                            ->label('Nama perusahaan')
                            ->required()
                            ->maxLength(200)
                            ->live(onBlur: true)
                            ->helperText(function (Get $get, ?Company $record): ?string {
                                $nama = trim((string) $get('nama'));

                                if (mb_strlen($nama) < 4) {
                                    return null;
                                }

                                $saran = app(CompanyResolver::class)
                                    ->suggest($nama, $get('alamat'))
                                    ->reject(fn (array $saran): bool => $record !== null
                                        && $saran['perusahaan']->getKey() === $record->getKey());

                                if ($saran->isEmpty()) {
                                    return null;
                                }

                                return 'Mirip dengan: '
                                    .$saran->map(fn (array $saran): string => $saran['perusahaan']->nama)->implode(', ')
                                    .'. Periksa dulu sebelum menyimpan — data mirip tidak otomatis ditolak.';
                            }),

                        Textarea::make('alamat')
                            ->label('Alamat')
                            ->required()
                            ->rows(3),

                        TextInput::make('email')
                            ->label('Email perusahaan')
                            ->email()
                            ->maxLength(150)
                            ->rule(fn (?Company $record): CompanyUniqueStrict => new CompanyUniqueStrict('email', $record?->getKey()))
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Set $set, ?string $state) => $set('email', CompanyNormalizer::email($state)))
                            ->dehydrateStateUsing(fn (?string $state): ?string => CompanyNormalizer::email($state)),

                        TextInput::make('website')
                            ->label('Website')
                            ->maxLength(200)
                            ->rule(fn (?Company $record): CompanyUniqueStrict => new CompanyUniqueStrict('website', $record?->getKey()))
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Set $set, ?string $state) => $set('website', CompanyNormalizer::website($state)))
                            ->placeholder('contoh.co.id')
                            ->dehydrateStateUsing(fn (?string $state): ?string => CompanyNormalizer::website($state)),

                        TextInput::make('telp_fax')
                            ->label('Telepon/fax')
                            ->maxLength(30)
                            ->rule(fn (?Company $record): CompanyUniqueStrict => new CompanyUniqueStrict('telp_fax', $record?->getKey()))
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Set $set, ?string $state) => $set('telp_fax', CompanyNormalizer::telp($state)))
                            ->placeholder('0215550001')
                            ->dehydrateStateUsing(fn (?string $state): ?string => CompanyNormalizer::telp($state)),

                        Select::make('source')
                            ->label('Sumber data')
                            ->options([
                                'hubin' => 'Hubin',
                                'siswa' => 'Siswa',
                                'import' => 'Impor',
                            ])
                            ->default('hubin')
                            ->required()
                            ->selectablePlaceholder(false),
                    ])
                    ->columns(2),

                Section::make('Kontak perusahaan')
                    ->components([
                        TextInput::make('cp_name')
                            ->label('Nama contact person')
                            ->maxLength(150),
                        TextInput::make('cp_phone')
                            ->label('Telepon contact person')
                            ->tel()
                            ->maxLength(30),
                    ])
                    ->columns(2),

                Section::make('Signer industri')
                    ->description('Nama dan email Pembimbing/Kepala Industri diverifikasi lewat email sebelum dipakai menandatangani dokumen.')
                    ->components([
                        TextInput::make('pembimbing_industri_nama')
                            ->label('Nama Pembimbing Industri')
                            ->maxLength(150)
                            ->rule(new SignerNameIndividual),
                        TextInput::make('pembimbing_industri_email')
                            ->label('Email Pembimbing Industri')
                            ->email()
                            ->maxLength(150)
                            ->helperText(fn (?Company $record): ?string => $record?->pembimbing_industri_email_verified_at !== null
                                ? 'Terverifikasi pada '.$record->pembimbing_industri_email_verified_at->timezone('Asia/Jakarta')->format('d M Y H:i').'.'
                                : 'Belum terverifikasi.'),
                        TextInput::make('kepala_industri_nama')
                            ->label('Nama Kepala Industri')
                            ->maxLength(150)
                            ->rule(new SignerNameIndividual),
                        TextInput::make('kepala_industri_email')
                            ->label('Email Kepala Industri')
                            ->email()
                            ->maxLength(150)
                            ->helperText(fn (?Company $record): ?string => $record?->kepala_industri_email_verified_at !== null
                                ? 'Terverifikasi pada '.$record->kepala_industri_email_verified_at->timezone('Asia/Jakarta')->format('d M Y H:i').'.'
                                : 'Belum terverifikasi.'),

                        Placeholder::make('status_signer')
                            ->label('Kesiapan signer')
                            ->content(fn (?Company $record): string => match (true) {
                                $record === null => 'Simpan perusahaan dulu untuk melihat status signer.',
                                $record->signerPembimbingSiap() && $record->signerKepalaSiap() => 'Siap — Pembimbing dan Kepala Industri sudah terverifikasi.',
                                $record->signerPembimbingSiap() => 'Sebagian — Pembimbing Industri siap; Kepala Industri belum siap.',
                                $record->signerKepalaSiap() => 'Sebagian — Kepala Industri siap; Pembimbing Industri belum siap.',
                                default => 'Belum siap — lengkapi nama/email lalu kirim verifikasi.',
                            })
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }
}
