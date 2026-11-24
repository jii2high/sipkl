<?php

namespace App\Filament\Hubin\Resources\Companies;

use App\Actions\Master\MergeCompany;
use App\Filament\Hubin\Concerns\HasImportAction;
use App\Filament\Hubin\Resources\Companies\Pages\ManageCompanies;
use App\Models\Company;
use App\Services\CompanyResolver;
use App\Support\ContactNormalizer;
use App\Support\SignerGuard;
use BackedEnum;
use Closure;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * Resource perusahaan panel Hubin (T2.1). Duplikat kunci unik dinormalisasi lewat
 * `ContactNormalizer` supaya sama dengan `CompanyResolver` yang dipakai impor dan
 * form siswa; kemiripan nama/alamat hanya memunculkan notifikasi peringatan.
 */
class CompanyResource extends Resource
{
    use HasImportAction;

    protected static ?string $model = Company::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static ?string $modelLabel = 'perusahaan';

    protected static ?string $pluralModelLabel = 'perusahaan';

    protected static ?string $recordTitleAttribute = 'nama';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identitas perusahaan')
                    ->schema([
                        TextInput::make('nama')
                            ->label('Nama')
                            ->required()
                            ->maxLength(200),
                        Textarea::make('alamat')
                            ->label('Alamat')
                            ->required()
                            ->rows(3),
                        TextInput::make('email')
                            ->label('Email')
                            ->maxLength(150)
                            ->dehydrateStateUsing(fn ($state) => ContactNormalizer::email($state))
                            ->rule(self::emailRule())
                            ->rule(self::uniqueContactRule('email')),
                        TextInput::make('website')
                            ->label('Website')
                            ->maxLength(200)
                            ->dehydrateStateUsing(fn ($state) => ContactNormalizer::website($state))
                            ->rule(self::uniqueContactRule('website')),
                        TextInput::make('telp_fax')
                            ->label('Telepon/faks')
                            ->tel()
                            ->maxLength(30)
                            ->dehydrateStateUsing(fn ($state) => ContactNormalizer::phone($state))
                            ->rule(self::uniqueContactRule('telp_fax')),
                        TextInput::make('cp_name')
                            ->label('Nama narahubung'),
                        TextInput::make('cp_phone')
                            ->label('Telepon narahubung')
                            ->tel()
                            ->maxLength(30),
                    ])
                    ->columns(2),
                Section::make('Signer industri')
                    ->description('Nama individu + email terverifikasi. Jabatan generik ditolak sebagai penerima OTP.')
                    ->schema([
                        TextInput::make('pembimbing_industri_nama')
                            ->label('Nama Pembimbing Industri')
                            ->maxLength(150)
                            ->rule(self::signerNameRule()),
                        TextInput::make('pembimbing_industri_email')
                            ->label('Email Pembimbing Industri')
                            ->email()
                            ->maxLength(150),
                        Toggle::make('pembimbing_industri_email_verified_at')
                            ->label('Email pembimbing terverifikasi')
                            ->live()
                            ->formatStateUsing(fn ($state): bool => (bool) $state)
                            ->dehydrateStateUsing(fn ($state) => $state ? now() : null)
                            ->rule(self::verifiedRequiresContactRule('pembimbing_industri')),
                        TextInput::make('kepala_industri_nama')
                            ->label('Nama Kepala Industri')
                            ->maxLength(150)
                            ->rule(self::signerNameRule()),
                        TextInput::make('kepala_industri_email')
                            ->label('Email Kepala Industri')
                            ->email()
                            ->maxLength(150),
                        Toggle::make('kepala_industri_email_verified_at')
                            ->label('Email kepala terverifikasi')
                            ->live()
                            ->formatStateUsing(fn ($state): bool => (bool) $state)
                            ->dehydrateStateUsing(fn ($state) => $state ? now() : null)
                            ->rule(self::verifiedRequiresContactRule('kepala_industri')),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nama')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('alamat')
                    ->label('Alamat')
                    ->limit(30)
                    ->searchable(),
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('telp_fax')
                    ->label('Telepon')
                    ->toggleable(),
                IconColumn::make('pembimbing_industri_email_verified_at')
                    ->label('Pembimbing terverifikasi')
                    ->boolean()
                    ->getStateUsing(fn (Company $record): bool => $record->pembimbing_industri_email_verified_at !== null),
                IconColumn::make('merged_into_id')
                    ->label('Digabung')
                    ->boolean()
                    ->getStateUsing(fn (Company $record): bool => $record->merged_into_id !== null),
                TextColumn::make('source')
                    ->label('Sumber')
                    ->badge()
                    ->toggleable(),
            ])
            ->filters([
                TernaryFilter::make('merged')
                    ->label('Hasil merge')
                    ->attribute('merged_into_id'),
            ])
            ->recordActions([
                EditAction::make()
                    ->after(fn (Company $record) => self::warnSimilar($record)),
                Action::make('merge')
                    ->label('Gabungkan')
                    ->icon(Heroicon::OutlinedArrowsRightLeft)
                    ->visible(fn (Company $record): bool => $record->merged_into_id === null
                        && auth()->user()?->can('merge', Company::class) === true)
                    ->schema([
                        Select::make('target_id')
                            ->label('Gabungkan ke perusahaan')
                            ->required()
                            ->searchable()
                            ->options(fn (Company $record) => Company::query()
                                ->whereNull('merged_into_id')
                                ->whereKeyNot($record->getKey())
                                ->orderBy('nama')
                                ->limit(200)
                                ->pluck('nama', 'id')
                                ->all()),
                    ])
                    ->action(function (Company $record, array $data): void {
                        $target = Company::query()->findOrFail($data['target_id']);

                        app(MergeCompany::class)->handle($record, $target, auth()->user());

                        Notification::make()
                            ->title('Perusahaan digabungkan')
                            ->body("Referensi \"{$record->nama}\" dipindah ke \"{$target->nama}\".")
                            ->success()
                            ->send();
                    }),
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
            'index' => ManageCompanies::route('/'),
        ];
    }

    /** Notifikasi lembut untuk nama/alamat yang mirip (BR-F2-2, tidak memblokir). */
    public static function warnSimilar(Company $record): void
    {
        $check = app(CompanyResolver::class)->inspect([
            'nama' => $record->nama,
            'alamat' => $record->alamat,
        ], (int) $record->getKey());

        if (! $check->hasWarnings()) {
            return;
        }

        Notification::make()
            ->title('Ada perusahaan yang mirip')
            ->body(implode(' ', $check->warnings))
            ->warning()
            ->send();
    }

    /** Format email dinilai setelah normalisasi agar spasi tepi tidak menolak input sah. */
    private static function emailRule(): Closure
    {
        return static fn (): Closure => static function (string $attribute, mixed $value, Closure $fail): void {
            $email = ContactNormalizer::email(is_string($value) ? $value : null);

            if ($email !== null && ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $fail(__('validation.email', ['attribute' => __('pkl.company.field.email')]));
            }
        };
    }

    /** Tolak email/website/telp yang sudah dipakai (dinormalisasi) — KP-F2-1. */
    private static function uniqueContactRule(string $field): Closure
    {
        return static function (Field $component, ?Company $record) use ($field): Closure {
            return function (string $attribute, mixed $value, Closure $fail) use ($field, $record): void {
                $normalized = ContactNormalizer::companyKeys([$field => $value])[$field] ?? null;

                if ($normalized === null) {
                    return;
                }

                $query = Company::query()->where($field, $normalized);

                if ($record instanceof Model) {
                    $query->whereKeyNot($record->getKey());
                }

                $existing = $query->first();

                if ($existing !== null) {
                    $fail(__('pkl.company.duplicate', [
                        'field' => __('pkl.company.field.'.$field),
                        'nama' => $existing->nama,
                    ]));
                }
            };
        };
    }

    /** Jabatan generik ditolak sebagai nama signer (BR-F2-3, KP-F2-4). */
    private static function signerNameRule(): Closure
    {
        return static function (): Closure {
            return function (string $attribute, mixed $value, Closure $fail): void {
                if (filled($value) && SignerGuard::isGeneric((string) $value)) {
                    $fail(__('pkl.company.signer_generic'));
                }
            };
        };
    }

    /** `verified_at` hanya boleh terisi bila nama dan email signer lengkap (CHECK DB). */
    private static function verifiedRequiresContactRule(string $prefix): Closure
    {
        return static fn (Get $get): Closure => function (string $attribute, mixed $value, Closure $fail) use ($get, $prefix): void {
            if (! $value) {
                return;
            }

            if (blank($get($prefix.'_nama')) || blank($get($prefix.'_email'))) {
                $fail(__('pkl.company.verified_requires_contact'));
            }
        };
    }
}
