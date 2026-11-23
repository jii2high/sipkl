<?php

namespace App\Filament\Hubin\Pages;

use App\Actions\Export\StartAccreditationExport;
use App\Models\AcademicPeriod;
use App\Models\AccreditationExport;
use App\Support\StatusChip;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

/**
 * Ekspor akreditasi Hubin (F13). Halaman hanya memicu percobaan dan menampilkan
 * statusnya; penulisan berkas ada di `AccreditationExportJob`.
 */
class EksporAkreditasi extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationLabel = 'Ekspor akreditasi';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowDownTray;

    protected static ?int $navigationSort = 12;

    public static function canAccess(): bool
    {
        return session('active_role') === 'hubin';
    }

    public function getTitle(): string
    {
        return __('pkl.export.judul');
    }

    public function getSubheading(): ?string
    {
        return __('pkl.export.subjudul', ['hari' => (int) config('pkl.export.retention_days')]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(AccreditationExport::query()->with(['academicPeriod', 'requester']))
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('academicPeriod.tahun_ajaran')
                    ->label(__('pkl.export.periode'))
                    ->placeholder(__('pkl.export.semua_periode')),
                TextColumn::make('status')
                    ->label(__('pkl.export.status'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => StatusChip::label($state))
                    ->color(fn (string $state): string => StatusChip::color($state)),
                TextColumn::make('row_count')
                    ->label(__('pkl.export.jumlah_baris'))
                    ->numeric()
                    ->placeholder('-'),
                TextColumn::make('requested_at')
                    ->label(__('pkl.export.diminta'))
                    ->dateTime('d M Y H:i'),
                TextColumn::make('completed_at')
                    ->label(__('pkl.export.selesai'))
                    ->dateTime('d M Y H:i')
                    ->placeholder('-'),
                TextColumn::make('expires_at')
                    ->label(__('pkl.export.kedaluwarsa_pada'))
                    ->dateTime('d M Y H:i')
                    ->placeholder('-'),
            ])
            ->recordActions([
                Action::make('download')
                    ->label(__('pkl.export.unduh'))
                    ->icon(Heroicon::OutlinedArrowDownTray)
                    ->url(fn (AccreditationExport $record): string => route('export.download', $record))
                    ->openUrlInNewTab()
                    ->visible(fn (AccreditationExport $record): bool => auth()->user()?->can('download', $record) ?? false),
                Action::make('retry')
                    ->label(__('pkl.export.retry'))
                    ->icon(Heroicon::OutlinedArrowPath)
                    ->requiresConfirmation()
                    ->visible(fn (AccreditationExport $record): bool => $record->status === 'failed')
                    ->action(function (AccreditationExport $record): void {
                        app(StartAccreditationExport::class)->handle(
                            auth()->user(),
                            $record->academic_period_id,
                            $record->getKey(),
                        );

                        Notification::make()->title(__('pkl.export.dimulai'))->success()->send();
                    }),
            ])
            ->emptyStateHeading(__('pkl.export.kosong'))
            ->emptyStateDescription(__('pkl.export.kosong_keterangan'));
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('start')
                ->label(__('pkl.export.mulai'))
                ->icon(Heroicon::OutlinedPlus)
                ->schema([
                    Select::make('academic_period_id')
                        ->label(__('pkl.export.periode'))
                        ->options(fn (): array => AcademicPeriod::query()
                            ->orderByDesc('tahun_ajaran')
                            ->pluck('tahun_ajaran', 'id')
                            ->all())
                        ->placeholder(__('pkl.export.semua_periode')),
                ])
                ->action(function (array $data): void {
                    app(StartAccreditationExport::class)->handle(
                        auth()->user(),
                        $data['academic_period_id'] ?? null,
                    );

                    Notification::make()->title(__('pkl.export.dimulai'))->success()->send();
                }),
        ];
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([EmbeddedTable::make()]);
    }
}
