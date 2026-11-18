<?php

namespace App\Filament\Hubin\Resources\Users;

use App\Actions\Master\ResetOtp;
use App\Actions\Master\SetAccountActive;
use App\Enums\Role;
use App\Filament\Hubin\Concerns\HasImportAction;
use App\Filament\Hubin\Resources\Users\Pages\ManageUsers;
use App\Models\Jurusan;
use App\Models\Kelas;
use App\Models\User;
use BackedEnum;
use Closure;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
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
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/**
 * Resource akun panel Hubin (T2.2). Akun tidak pernah dihapus (jejak akademik):
 * hanya dinonaktifkan lewat `SetAccountActive`; akses OTP direset lewat `ResetOtp`.
 * Scope jurusan/kelas per peran disimpan di `user_roles` (rangkap peran).
 */
class UserResource extends Resource
{
    use HasImportAction;

    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?string $modelLabel = 'akun';

    protected static ?string $pluralModelLabel = 'akun';

    protected static ?string $recordTitleAttribute = 'name';

    /** Peran yang boleh diatur Hubin dari resource ini. */
    private const MANAGEABLE_ROLES = ['siswa', 'guru_pembimbing', 'walas', 'kaprog'];

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Akun')
                    ->schema([
                        TextInput::make('nis_nip')
                            ->label('NIS/NIP')
                            ->required()
                            ->maxLength(30)
                            ->unique('users', 'nis_nip', ignoreRecord: true),
                        TextInput::make('name')
                            ->label('Nama')
                            ->required()
                            ->maxLength(150),
                        TextInput::make('email')
                            ->label('Email')
                            ->required()
                            ->maxLength(150)
                            ->unique('users', 'email', ignoreRecord: true)
                            ->rule(self::schoolDomainRule()),
                        Select::make('user_type')
                            ->label('Jenis akun')
                            ->options([
                                'siswa' => 'Siswa',
                                'guru' => 'Guru',
                                'hubin' => 'Hubin',
                                'kepsek' => 'Kepala Sekolah',
                            ])
                            ->required()
                            ->live(),
                        Toggle::make('is_active')
                            ->label('Akun aktif'),
                    ])
                    ->columns(2),
                Section::make('Scope siswa')
                    ->description('Wajib untuk akun siswa (CHECK ck_users_siswa_scope).')
                    ->visible(fn (Get $get): bool => $get('user_type') === 'siswa')
                    ->schema([
                        Select::make('jurusan_id')
                            ->label('Jurusan')
                            ->options(fn (): array => self::jurusanOptions())
                            ->required(fn (Get $get): bool => $get('user_type') === 'siswa'),
                        Select::make('kelas_id')
                            ->label('Kelas')
                            ->options(fn (): array => self::kelasOptions())
                            ->required(fn (Get $get): bool => $get('user_type') === 'siswa'),
                    ])
                    ->columns(2),
                Section::make('Peran')
                    ->description('Satu akun dapat memegang beberapa peran; kaprog butuh jurusan, wali kelas butuh kelas.')
                    ->schema([
                        Repeater::make('roles')
                            ->label('Peran')
                            ->relationship()
                            ->schema([
                                Select::make('role')
                                    ->label('Peran')
                                    ->options(fn (): array => self::roleOptions())
                                    ->required()
                                    ->live(),
                                Select::make('jurusan_id')
                                    ->label('Scope jurusan')
                                    ->options(fn (): array => self::jurusanOptions())
                                    ->visible(fn (Get $get): bool => $get('role') === Role::Kaprog->value)
                                    ->required(fn (Get $get): bool => $get('role') === Role::Kaprog->value),
                                Select::make('kelas_id')
                                    ->label('Scope kelas')
                                    ->options(fn (): array => self::kelasOptions())
                                    ->visible(fn (Get $get): bool => $get('role') === Role::Walas->value)
                                    ->required(fn (Get $get): bool => $get('role') === Role::Walas->value),
                            ])
                            ->columns(3)
                            ->defaultItems(0)
                            ->addActionLabel('Tambah peran'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nis_nip')
                    ->label('NIS/NIP')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('user_type')
                    ->label('Jenis')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'siswa' => 'Siswa',
                        'guru' => 'Guru',
                        'hubin' => 'Hubin',
                        'kepsek' => 'Kepala Sekolah',
                        default => $state,
                    }),
                TextColumn::make('roles.role')
                    ->label('Peran')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => Role::tryFrom($state)?->label() ?? $state),
                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('user_type')
                    ->label('Jenis akun')
                    ->options(['guru' => 'Guru', 'siswa' => 'Siswa']),
                SelectFilter::make('is_active')
                    ->label('Status')
                    ->options(['1' => 'Aktif', '0' => 'Nonaktif']),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('resetOtp')
                    ->label('Reset OTP')
                    ->icon(Heroicon::OutlinedKey)
                    ->requiresConfirmation()
                    ->modalDescription('OTP aktif dibatalkan dan akun kembali ke keadaan login pertama. Kode baru diterbitkan saat pengguna login.')
                    ->visible(fn (User $record): bool => auth()->user()?->can('resetOtp', $record) === true)
                    ->action(function (User $record): void {
                        app(ResetOtp::class)->handle($record, auth()->user());

                        Notification::make()
                            ->title(__('pkl.account.otp_reset', ['nama' => $record->name]))
                            ->success()
                            ->send();
                    }),
                Action::make('toggleActive')
                    ->label(fn (User $record): string => $record->is_active ? 'Nonaktifkan' : 'Aktifkan')
                    ->icon(Heroicon::OutlinedNoSymbol)
                    ->requiresConfirmation()
                    ->visible(fn (User $record): bool => auth()->user()?->can('toggleActive', $record) === true)
                    ->action(function (User $record): void {
                        $active = ! $record->is_active;

                        app(SetAccountActive::class)->handle($record, $active, auth()->user());

                        Notification::make()
                            ->title($active
                                ? __('pkl.account.activated', ['nama' => $record->name])
                                : __('pkl.account.deactivated', ['nama' => $record->name]))
                            ->success()
                            ->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageUsers::route('/')];
    }

    /** Email akun wajib memakai domain sekolah (CHECK ck_users_domain). */
    private static function schoolDomainRule(): Closure
    {
        return static fn (): Closure => static function (string $attribute, mixed $value, Closure $fail): void {
            $domain = (string) config('pkl.school.email_domain');

            if (filled($value) && ! str_ends_with(strtolower((string) $value), '@'.strtolower($domain))) {
                $fail(__('pkl.email.domain', ['domain' => $domain]));
            }
        };
    }

    /**
     * @return array<int, string>
     */
    private static function jurusanOptions(): array
    {
        return Jurusan::query()->orderBy('nama')->pluck('nama', 'id')->all();
    }

    /**
     * @return array<int, string>
     */
    private static function kelasOptions(): array
    {
        return Kelas::query()->orderBy('nama')->pluck('nama', 'id')->all();
    }

    /**
     * @return array<string, string>
     */
    private static function roleOptions(): array
    {
        $options = [];

        foreach (self::MANAGEABLE_ROLES as $value) {
            $role = Role::tryFrom($value);

            if ($role !== null) {
                $options[$value] = $role->label();
            }
        }

        return $options;
    }
}
