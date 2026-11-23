<?php

namespace App\Filament\Hubin\Pages;

use App\Actions\Industri\GenerateIndustryLink;
use App\Actions\Industri\ReissueIndustryLink;
use App\Actions\Industri\RevokeIndustryLink;
use App\Enums\Role;
use App\Models\FormDefinition;
use App\Models\FormSubmission;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Validation\ValidationException;

/**
 * Override Hubin untuk tautan penilaian industri (F10/BR-F10-3): cabut atau
 * terbitkan ulang dengan alasan wajib yang masuk audit.
 */
class PenilaianIndustri extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationLabel = 'Penilaian industri';

    protected static ?string $slug = 'penilaian-industri';

    protected static ?string $title = 'Penilaian Industri';

    protected static ?int $navigationSort = 5;

    public static function canAccess(): bool
    {
        return session('active_role') === Role::Hubin->value;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                FormSubmission::query()
                    ->with(['company', 'academicPeriod', 'definition', 'students.pengajuanSiswa.siswa'])
                    ->where('form_type', FormDefinition::TYPE_INDUSTRI)
            )
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('company.nama')
                    ->label('Perusahaan')
                    ->searchable()
                    ->wrap(),
                TextColumn::make('definition.nama')
                    ->label('Form')
                    ->toggleable(),
                TextColumn::make('academicPeriod.tahun_ajaran')
                    ->label('Periode')
                    ->toggleable(),
                TextColumn::make('tautan')
                    ->label('Status tautan')
                    ->badge()
                    ->state(fn (FormSubmission $record): string => $this->stateLabels()[$record->industryLinkState()]
                        ?? $record->industryLinkState())
                    ->color(fn (FormSubmission $record): string => match ($record->industryLinkState()) {
                        'active' => 'success',
                        'expired' => 'warning',
                        'final' => 'info',
                        default => 'gray',
                    }),
                TextColumn::make('nilai_total')
                    ->label('Nilai')
                    ->numeric()
                    ->placeholder('-'),
                TextColumn::make('students')
                    ->label('Siswa')
                    ->state(fn (FormSubmission $record): string => $record->students
                        ->pluck('pengajuanSiswa.siswa.name')
                        ->filter()
                        ->join(', '))
                    ->wrap(),
            ])
            ->recordActions([
                Action::make('issue')
                    ->label('Terbitkan tautan')
                    ->icon(Heroicon::OutlinedLink)
                    ->visible(fn (FormSubmission $record): bool => in_array(
                        $record->industryLinkState(),
                        ['none', 'expired'],
                        true,
                    ))
                    ->action(function (FormSubmission $record): void {
                        try {
                            $result = app(GenerateIndustryLink::class)->handle(
                                $record->company,
                                $record->academicPeriod,
                                $record->definition,
                                auth()->user(),
                            );
                        } catch (ValidationException $exception) {
                            $this->notifyValidation($exception);

                            return;
                        }

                        $this->notifyLink($result['url'], __('pkl.industri.link_issued'));
                    }),
                Action::make('revoke')
                    ->label('Cabut tautan')
                    ->icon(Heroicon::OutlinedNoSymbol)
                    ->color('danger')
                    ->visible(fn (FormSubmission $record): bool => $record->industryLinkState() === 'active')
                    ->schema([$this->reasonField()])
                    ->action(function (FormSubmission $record, array $data): void {
                        try {
                            app(RevokeIndustryLink::class)->handle($record, auth()->user(), $data['reason']);
                        } catch (ValidationException $exception) {
                            $this->notifyValidation($exception);

                            return;
                        }

                        Notification::make()->title(__('pkl.industri.link_revoked'))->success()->send();
                    }),
                Action::make('reissue')
                    ->label('Terbitkan ulang')
                    ->icon(Heroicon::OutlinedArrowPath)
                    ->visible(fn (FormSubmission $record): bool => $record->industryLinkState() === 'active')
                    ->schema([$this->reasonField()])
                    ->action(function (FormSubmission $record, array $data): void {
                        try {
                            $result = app(ReissueIndustryLink::class)->handle(
                                $record,
                                auth()->user(),
                                $data['reason'],
                            );
                        } catch (ValidationException $exception) {
                            $this->notifyValidation($exception);

                            return;
                        }

                        $this->notifyLink($result['url'], __('pkl.industri.link_reissued'));
                    }),
            ])
            ->emptyStateHeading('Belum ada tautan penilaian industri yang diterbitkan');
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([EmbeddedTable::make()]);
    }

    private function reasonField(): Textarea
    {
        return Textarea::make('reason')
            ->label('Alasan override Hubin (minimal 10 karakter)')
            ->required()
            ->minLength((int) config('pkl.rejection_note.min_length', 10))
            ->maxLength(500);
    }

    /** @return array<string, string> */
    private function stateLabels(): array
    {
        return [
            'none' => __('pkl.industri.state_none'),
            'active' => __('pkl.industri.state_active'),
            'expired' => __('pkl.industri.state_expired'),
            'final' => __('pkl.industri.state_final'),
        ];
    }

    private function notifyLink(string $url, string $title): void
    {
        Notification::make()
            ->title($title)
            ->body($url.' · '.__('pkl.industri.link_email_note'))
            ->persistent()
            ->success()
            ->send();
    }

    private function notifyValidation(ValidationException $exception): void
    {
        Notification::make()
            ->title($exception->errors()[array_key_first($exception->errors())][0] ?? 'Data belum valid')
            ->danger()
            ->send();
    }
}
