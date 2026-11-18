<?php

namespace App\Filament\Hubin\Resources\SignatureAssets;

use App\Actions\Master\UploadKepsekSignature;
use App\Filament\Hubin\Resources\SignatureAssets\Pages\ManageSignatureAssets;
use App\Models\SignatureAsset;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Resource scan TTD Kepsek pada panel Hubin (BR-F2-5, T2.2). Hanya satu scan aktif;
 * unggahan baru menonaktifkan yang lama lewat `UploadKepsekSignature`, dan berkas
 * disimpan privat di disk `local` (unduhan berotorisasi menyusul di T3.1).
 */
class SignatureAssetResource extends Resource
{
    protected static ?string $model = SignatureAsset::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPencilSquare;

    protected static ?string $modelLabel = 'scan TTD Kepsek';

    protected static ?string $pluralModelLabel = 'scan TTD Kepsek';

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('owner.name')
                    ->label('Pemilik'),
                TextColumn::make('file_path')
                    ->label('Berkas')
                    ->formatStateUsing(fn (string $state): string => basename($state)),
                TextColumn::make('uploader.name')
                    ->label('Diunggah oleh'),
                TextColumn::make('created_at')
                    ->label('Waktu')
                    ->dateTime('d M Y H:i')
                    ->timezone(config('pkl.timezone.operational')),
                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageSignatureAssets::route('/')];
    }

    /** Unggah scan baru; hanya satu yang aktif (BR-F2-5). */
    public static function uploadAction(): Action
    {
        return Action::make('upload')
            ->label('Unggah scan TTD')
            ->icon(Heroicon::OutlinedArrowUpTray)
            ->visible(fn (): bool => auth()->user()?->can('create', SignatureAsset::class) === true)
            ->modalDescription(__('pkl.signature.hint'))
            ->schema([
                FileUpload::make('file')
                    ->label('Berkas (PNG/JPG/PDF)')
                    ->disk('local')
                    ->storeFiles(false)
                    ->acceptedFileTypes(['image/png', 'image/jpeg', 'application/pdf'])
                    ->maxSize((int) config('pkl.signature.max_mb') * 1024)
                    ->required(),
            ])
            ->action(function (array $data): void {
                app(UploadKepsekSignature::class)->handle($data['file'], auth()->user());

                Notification::make()
                    ->title(__('pkl.signature.uploaded'))
                    ->success()
                    ->send();
            });
    }
}
