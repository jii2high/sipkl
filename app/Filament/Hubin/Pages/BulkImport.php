<?php

namespace App\Filament\Hubin\Pages;

use App\Exports\TemplateExport;
use App\Imports\CompaniesImport;
use App\Imports\ImportReport;
use App\Imports\PeriodsImport;
use App\Imports\UsersImport;
use App\Services\ActiveRole;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Throwable;
use UnitEnum;

/**
 * Impor massal perusahaan, akun, dan periode (TASKS M2.9).
 *
 * Impor memakai normalisasi/aturan yang sama dengan form Hubin. Baris valid
 * tetap masuk walau baris lain gagal; hasil per baris ditampilkan setelah impor.
 */
class BulkImport extends Page
{
    /**
     * @var array<string, string>
     */
    public const JENIS = [
        'perusahaan' => 'Perusahaan',
        'akun' => 'Akun pengguna',
        'periode' => 'Periode akademik',
    ];

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowUpTray;

    protected static string|UnitEnum|null $navigationGroup = 'Master Data';

    protected static ?string $navigationLabel = 'Impor Massal';

    protected static ?string $title = 'Impor Massal';

    protected static ?int $navigationSort = 4;

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    /**
     * @var array<string, mixed>|null
     */
    public ?array $hasil = null;

    public static function canAccess(): bool
    {
        return app(ActiveRole::class)->is('hubin');
    }

    public function mount(): void
    {
        $this->form->fill(['jenis' => 'perusahaan']);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Berkas impor')
                    ->description('Unduh template sesuai jenis data, isi tanpa mengubah judul kolom, lalu unggah.')
                    ->components([
                        Select::make('jenis')
                            ->label('Jenis data')
                            ->options(self::JENIS)
                            ->default('perusahaan')
                            ->required()
                            ->selectablePlaceholder(false),

                        FileUpload::make('file')
                            ->label('Berkas (.xlsx / .csv)')
                            ->acceptedFileTypes([
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                'application/vnd.ms-excel',
                                'application/zip',
                                'application/x-zip-compressed',
                                'text/csv',
                                'text/plain',
                            ])
                            ->maxSize(5120)
                            ->required()
                            ->storeFiles(false)
                            ->helperText('Maksimal 5 MB. Baris bermasalah dilaporkan, baris valid tetap disimpan.'),
                    ])
                    ->columns(2),
            ])
            ->statePath('data');
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                Form::make([EmbeddedSchema::make('form')])
                    ->id('bulk-import')
                    ->livewireSubmitHandler('import')
                    ->footer([
                        Actions::make([
                            Action::make('import')
                                ->label('Impor sekarang')
                                ->icon(Heroicon::OutlinedArrowUpTray)
                                ->submit('import'),
                        ]),
                    ]),
            ]);
    }

    /**
     * @return array<Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('unduhTemplate')
                ->label('Unduh template')
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->color('gray')
                ->action(fn (): BinaryFileResponse => $this->unduhTemplate()),
        ];
    }

    public function unduhTemplate(): BinaryFileResponse
    {
        $jenis = $this->data['jenis'] ?? 'perusahaan';

        [$judul, $headings, $contoh] = match ($jenis) {
            'akun' => [
                'Akun',
                ['NIS/NIP', 'Nama Lengkap', 'Email Sekolah', 'Tipe Pengguna', 'Jurusan', 'Kelas', 'Aktif'],
                ['123456', 'Contoh Nama Siswa', 'contoh.siswa@smkn1kotabekasi.sch.id', 'siswa', 'RPL', 'XI RPL 1', 'ya'],
            ],
            'periode' => [
                'Periode',
                ['Tahun Ajaran', 'Batch', 'Tanggal Mulai', 'Tanggal Selesai', 'Aktif', 'Kunci Pengajuan Baru'],
                ['2026/2027', '1', '2026-07-01', '2026-12-31', 'ya', 'tidak'],
            ],
            default => [
                'Perusahaan',
                [
                    'Nama Perusahaan',
                    'Alamat',
                    'Email',
                    'Website',
                    'Telepon/Fax',
                    'Nama Kontak',
                    'Telepon Kontak',
                    'Nama Pembimbing Industri',
                    'Email Pembimbing Industri',
                    'Nama Kepala Industri',
                    'Email Kepala Industri',
                ],
                [
                    'PT Contoh Sentosa',
                    'Jl. Merdeka No. 1, Bekasi',
                    'hrd@contoh.co.id',
                    'contoh.co.id',
                    '0215550001',
                    'Budi Santoso',
                    '081234567890',
                    '',
                    '',
                    '',
                    '',
                ],
            ],
        };

        return Excel::download(
            new TemplateExport($headings, [$contoh], $judul),
            'template-impor-'.$jenis.'.xlsx',
        );
    }

    public function import(): void
    {
        /** @var array{jenis: string, file: mixed} $data */
        $data = $this->form->getState();

        $berkas = $data['file'];

        if (! $berkas instanceof UploadedFile) {
            Notification::make()
                ->title('Berkas impor belum terunggah')
                ->body('Pilih berkas terlebih dahulu lalu coba lagi.')
                ->danger()
                ->send();

            return;
        }

        $jenis = $data['jenis'];
        $report = new ImportReport;
        $oleh = Auth::user();

        $import = match ($jenis) {
            'akun' => new UsersImport($report, $oleh),
            'periode' => new PeriodsImport($report, $oleh),
            default => new CompaniesImport($report, $oleh),
        };

        try {
            Excel::import($import, $berkas->getRealPath());
        } catch (Throwable $exception) {
            report($exception);

            Notification::make()
                ->title('Berkas tidak bisa dibaca')
                ->body('Pastikan berkas memakai template terbaru dan tidak rusak.')
                ->danger()
                ->send();

            return;
        }

        $this->hasil = [
            ...$report->toArray(),
            'jenis' => self::JENIS[$jenis] ?? $jenis,
        ];

        $this->form->fill(['jenis' => $jenis]);

        activity('import')
            ->causedBy($oleh)
            ->withProperties([
                'jenis' => $jenis,
                'total' => $report->total(),
                'berhasil' => $report->jumlahBerhasil(),
                'gagal' => $report->jumlahGagal(),
                'perlu_tinjau' => $report->jumlahPerluTinjau(),
            ])
            ->log('import.massal');

        $notification = Notification::make()
            ->title('Impor selesai')
            ->body($report->ringkasan());

        if ($report->jumlahGagal() > 0) {
            $notification->warning()->send();
        } else {
            $notification->success()->send();
        }
    }
}
