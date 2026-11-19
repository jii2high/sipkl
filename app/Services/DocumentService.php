<?php

namespace App\Services;

use App\Exceptions\DocumentIssuanceHeld;
use App\Jobs\GenerateDocumentPdfJob;
use App\Models\DocumentQrToken;
use App\Models\PengajuanPkl;
use App\Models\SignatureAsset;
use App\Models\SuratTugasPembimbing;
use App\Models\SuratTugasVisitasi;
use App\Models\User;
use App\Notifications\Notification;
use App\Support\JakartaTime;
use App\Support\StatusChip;
use Barryvdh\DomPDF\Facade\Pdf;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\ErrorCorrectionLevel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use LogicException;
use RuntimeException;

/**
 * Dokumen surat: nomor (F12), PDF via queue, TTD Kepsek, token QR verifikasi
 * (TRD.md §11). Satu layanan melayani tiga jenis surat supaya M4-M5 cukup
 * memanggil `issue()`; template dan perakitan data tinggal di sini.
 *
 * Token QR bersifat deterministik: HMAC(APP_KEY, "jenis:id"). Hash-nya disimpan
 * di `document_qr_tokens`, token mentahnya tidak pernah masuk database maupun
 * payload job. Karena itu render ulang PDF (retry) memakai nomor **dan** token
 * yang sama tanpa menyimpan rahasia apa pun.
 */
final class DocumentService
{
    public const JENIS_PENGANTAR = LetterNumberService::JENIS_SURAT_PENGANTAR;

    public const JENIS_TUGAS_PEMBIMBING = LetterNumberService::JENIS_SURAT_TUGAS_PEMBIMBING;

    public const JENIS_TUGAS_VISITASI = LetterNumberService::JENIS_SURAT_TUGAS_VISITASI;

    /** Jenis surat → model baris dokumen. */
    private const MODELS = [
        self::JENIS_PENGANTAR => PengajuanPkl::class,
        self::JENIS_TUGAS_PEMBIMBING => SuratTugasPembimbing::class,
        self::JENIS_TUGAS_VISITASI => SuratTugasVisitasi::class,
    ];

    /** Jenis surat → kolom "tanggal terbit" (null = tidak ada kolomnya). */
    private const ISSUED_AT_COLUMN = [
        self::JENIS_PENGANTAR => 'issued_at',
        self::JENIS_TUGAS_PEMBIMBING => 'signed_at',
        self::JENIS_TUGAS_VISITASI => null,
    ];

    /** Jenis surat → template PDF (resources/views). */
    private const VIEWS = [
        self::JENIS_PENGANTAR => 'pdf.surat-pengantar',
        self::JENIS_TUGAS_PEMBIMBING => 'pdf.surat-tugas-pembimbing',
        self::JENIS_TUGAS_VISITASI => 'pdf.surat-tugas-visitasi',
    ];

    public function __construct(
        private readonly LetterNumberService $numbers,
        private readonly NotificationService $notifications,
    ) {}

    /** @return class-string<Model> */
    public static function modelClass(string $jenisSurat): string
    {
        if (! isset(self::MODELS[$jenisSurat])) {
            throw new RuntimeException("Jenis surat tidak dikenal: {$jenisSurat}.");
        }

        return self::MODELS[$jenisSurat];
    }

    /**
     * Terbitkan nomor surat + token QR + baris TTD, lalu antrekan pembuatan PDF.
     * Idempoten: dokumen yang sudah bernomor mengembalikan nomor lama tanpa
     * mengambil nomor baru (BR-F12-4, KP-F12-2).
     */
    public function issue(Model $document, string $jenisSurat, string $tahunAjaran, ?User $issuedBy = null): Model
    {
        $this->assertDocument($document, $jenisSurat);

        if (! $document->exists && $document instanceof SuratTugasPembimbing) {
            return $this->issueNewSupervisorLetter($document, $tahunAjaran, $issuedBy);
        }

        if ($document->getAttribute('nomor_surat') !== null) {
            return $document;
        }

        $signature = $this->activeSignature() ?? $this->hold();

        DB::transaction(function () use ($document, $jenisSurat, $tahunAjaran, $issuedBy, $signature): void {
            $nomor = $this->numbers->allocate($tahunAjaran, $jenisSurat);

            $attributes = [
                'tahun_ajaran' => $tahunAjaran,
                'nomor_urut' => $nomor['nomor_urut'],
                'nomor_surat' => $nomor['nomor_surat'],
                'signature_asset_id' => $signature->getKey(),
            ];

            if (($column = self::ISSUED_AT_COLUMN[$jenisSurat]) !== null) {
                $attributes[$column] = now();
            }

            if ($jenisSurat === self::JENIS_PENGANTAR && $issuedBy !== null) {
                $attributes['issued_by'] = $issuedBy->getKey();
            }

            $document->newQuery()->whereKey($document->getKey())->update($attributes);

            $this->ensureQrToken($jenisSurat, (int) $document->getKey(), $nomor['nomor_surat']);

            app(AuditService::class)->log(
                description: 'Nomor surat diterbitkan',
                subject: $document,
                event: 'document_issued',
                properties: [
                    'jenis_surat' => $jenisSurat,
                    'tahun_ajaran' => $tahunAjaran,
                    'nomor_surat' => $nomor['nomor_surat'],
                ],
                causer: $issuedBy,
            );
        });

        $document->refresh();

        // PDF dibuat di queue; kegagalan tidak membuat nomor terpakai tanpa dokumen
        // (BR-F12-4). Retry memakai nomor dan path yang sama.
        GenerateDocumentPdfJob::dispatch($jenisSurat, (int) $document->getKey());

        return $document;
    }

    private function issueNewSupervisorLetter(SuratTugasPembimbing $document, string $tahunAjaran, ?User $issuedBy): SuratTugasPembimbing
    {
        $signature = $this->activeSignature() ?? $this->hold();

        DB::transaction(function () use ($document, $tahunAjaran, $issuedBy, $signature): void {
            $number = $this->numbers->allocate($tahunAjaran, self::JENIS_TUGAS_PEMBIMBING);

            $document->fill([
                'tahun_ajaran' => $tahunAjaran,
                'guru_id' => $document->plotting()->value('guru_id'),
                'nomor_urut' => $number['nomor_urut'],
                'nomor_surat' => $number['nomor_surat'],
                'signature_asset_id' => $signature->getKey(),
                'signed_at' => now(),
            ])->save();

            $this->ensureQrToken(self::JENIS_TUGAS_PEMBIMBING, (int) $document->getKey(), $number['nomor_surat']);

            app(AuditService::class)->log(
                description: 'Surat tugas pembimbing diterbitkan',
                subject: $document,
                event: 'document_issued',
                properties: [
                    'jenis_surat' => self::JENIS_TUGAS_PEMBIMBING,
                    'tahun_ajaran' => $tahunAjaran,
                    'nomor_surat' => $number['nomor_surat'],
                ],
                causer: $issuedBy,
            );
        });

        $document->refresh();
        GenerateDocumentPdfJob::dispatch(self::JENIS_TUGAS_PEMBIMBING, (int) $document->getKey());

        return $document;
    }

    /**
     * Buat PDF dokumen dan simpan di disk privat. Idempoten: path diturunkan dari
     * nomor surat, jadi retry menimpa berkas yang sama, bukan nomor baru.
     */
    public function renderPdf(string $jenisSurat, int $documentId): string
    {
        $document = $this->find($jenisSurat, $documentId);

        if ($document === null) {
            throw new RuntimeException('Dokumen tidak ditemukan.');
        }

        $nomorSurat = $document->getAttribute('nomor_surat');

        if (! is_string($nomorSurat) || $nomorSurat === '') {
            throw new RuntimeException('Dokumen belum bernomor; PDF tidak dapat dibuat.');
        }

        $html = view(self::VIEWS[$jenisSurat], $this->viewData($jenisSurat, $document))->render();

        $pdf = Pdf::loadHTML($html)->setPaper('a4', 'portrait');

        $path = sprintf('documents/%s/%s.pdf', $jenisSurat, $this->slug($nomorSurat));

        if (Storage::disk('local')->put($path, $pdf->output()) === false) {
            throw new RuntimeException('PDF dokumen gagal disimpan.');
        }

        $document->newQuery()->whereKey($document->getKey())->update(['file_path' => $path]);

        return $path;
    }

    /**
     * Tiga field yang boleh tampil di `/verify`: nomor surat, status, tanggal
     * terbit. Token tidak dikenal atau dicabut mengembalikan null (BR-F12-3).
     *
     * @return array{nomor_surat: string, status: string, tanggal_terbit: string}|null
     */
    public function verify(string $token): ?array
    {
        if ($token === '') {
            return null;
        }

        $row = DocumentQrToken::query()
            ->where('token_hash', $this->hash($token))
            ->where('status', 'valid')
            ->first();

        if ($row === null) {
            return null;
        }

        $document = $this->find($row->document_type, (int) $row->document_id);

        return [
            'nomor_surat' => $row->nomor_surat,
            'status' => $this->statusLabel($document),
            'tanggal_terbit' => JakartaTime::humanDate($row->issued_at),
        ];
    }

    /** URL publik halaman verifikasi untuk sebuah dokumen. */
    public function verificationUrl(string $jenisSurat, int $documentId): string
    {
        return route('verify', ['token' => $this->verificationToken($jenisSurat, $documentId)]);
    }

    /** Token mentah (deterministik) untuk QR; tidak disimpan di database. */
    public function verificationToken(string $jenisSurat, int $documentId): string
    {
        $key = config('app.key');

        if (! is_string($key) || $key === '') {
            throw new LogicException('APP_KEY wajib terisi untuk membuat token verifikasi.');
        }

        return hash_hmac('sha256', $jenisSurat.':'.$documentId, $key);
    }

    /** Tandai token verifikasi dicabut; halaman `/verify` lalu menjawab netral. */
    public function revoke(Model $document, string $jenisSurat, ?User $by = null, ?string $reason = null): void
    {
        DocumentQrToken::query()
            ->where('document_type', $jenisSurat)
            ->where('document_id', $document->getKey())
            ->where('status', 'valid')
            ->update([
                'status' => 'dicabut',
                'revoked_at' => now(),
                'revoked_by' => $by?->getKey(),
                'revoke_reason' => $reason,
            ]);
    }

    /** QR sebagai data URI agar bisa disematkan dalam HTML dompdf. */
    public function qrDataUri(string $url): string
    {
        return (new Builder)
            ->build(
                data: $url,
                size: 240,
                margin: 0,
                errorCorrectionLevel: ErrorCorrectionLevel::Medium,
            )
            ->getDataUri();
    }

    /** Scan TTD Kepsek aktif, atau null bila belum diunggah (BR-F2-5). */
    public function activeSignature(): ?SignatureAsset
    {
        return SignatureAsset::query()->where('is_active', true)->first();
    }

    private function ensureQrToken(string $jenisSurat, int $documentId, string $nomorSurat): void
    {
        DocumentQrToken::query()->firstOrCreate(
            ['document_type' => $jenisSurat, 'document_id' => $documentId],
            [
                'token_hash' => $this->hash($this->verificationToken($jenisSurat, $documentId)),
                'nomor_surat' => $nomorSurat,
                'status' => 'valid',
                'issued_at' => now(),
            ],
        );
    }

    /**
     * Simpan penerbitan: beri tahu Hubin lalu lemparkan exception domain supaya
     * pemanggil (Action M4/M5) dapat menampilkan pesan yang sama.
     */
    private function hold(): never
    {
        $hubin = User::query()
            ->whereHas('roles', fn ($query) => $query->where('role', 'hubin'))
            ->get();

        foreach ($hubin as $user) {
            $this->notifications->send(
                $user,
                new Notification(
                    'Penerbitan surat ditahan',
                    'Scan TTD Kepsek belum tersedia. Unggah scan pada panel Hubin lalu terbitkan ulang surat tersebut.',
                    url('/hubin/signature-assets'),
                ),
                ['related' => null],
            );
        }

        throw DocumentIssuanceHeld::noSignature();
    }

    private function find(string $jenisSurat, int $documentId): ?Model
    {
        $class = self::modelClass($jenisSurat);

        return $class::query()->find($documentId);
    }

    private function assertDocument(Model $document, string $jenisSurat): void
    {
        $class = self::modelClass($jenisSurat);

        if (! $document instanceof $class) {
            throw new RuntimeException("Dokumen tidak sesuai jenis surat {$jenisSurat}.");
        }
    }

    private function statusLabel(?Model $document): string
    {
        // Hanya surat tugas visitasi yang punya status kerja bermakna bagi publik
        // (mis. "Aktif", "Kedaluwarsa"). Surat lain selalu "Terbit".
        if ($document instanceof SuratTugasVisitasi) {
            $status = $document->getAttribute('status');

            if (is_string($status)) {
                return StatusChip::label($status);
            }
        }

        return __('pkl.document.issued');
    }

    private function slug(string $nomorSurat): string
    {
        return strtolower((string) preg_replace('/[^A-Za-z0-9]+/', '-', $nomorSurat));
    }

    private function hash(string $token): string
    {
        return hash('sha256', $token);
    }

    /**
     * Data untuk template PDF, dipisah per jenis surat (TRD.md §11).
     *
     * @return array<string, mixed>
     */
    private function viewData(string $jenisSurat, Model $document): array
    {
        $signature = $document->getAttribute('signature_asset_id') !== null
            ? SignatureAsset::query()->find($document->getAttribute('signature_asset_id'))
            : null;

        $url = $this->verificationUrl($jenisSurat, (int) $document->getKey());

        $base = [
            'kop' => (array) config('pkl.school.kop'),
            'nomor_surat' => (string) $document->getAttribute('nomor_surat'),
            'tanggal' => JakartaTime::humanDate($this->issuedAt($jenisSurat, $document)),
            'verifikasi' => ['url' => $url, 'qr' => $this->qrDataUri($url)],
            'ttd' => $this->signatureData($signature),
        ];

        return $base + match ($jenisSurat) {
            self::JENIS_PENGANTAR => $this->pengantarData($document),
            self::JENIS_TUGAS_PEMBIMBING => $this->tugasPembimbingData($document),
            self::JENIS_TUGAS_VISITASI => $this->tugasVisitasiData($document),
        };
    }

    private function issuedAt(string $jenisSurat, Model $document): mixed
    {
        $column = self::ISSUED_AT_COLUMN[$jenisSurat];

        if ($column !== null && $document->getAttribute($column) !== null) {
            return $document->getAttribute($column);
        }

        return $document->getAttribute('created_at') ?? JakartaTime::now();
    }

    /**
     * @return array<string, mixed>
     */
    private function signatureData(?SignatureAsset $signature): array
    {
        if ($signature === null) {
            return ['nama' => null, 'nip' => null, 'jabatan' => __('pkl.document.kepsek_jabatan'), 'image' => null];
        }

        $owner = $signature->owner;
        $image = null;

        // PDF TTD hasil pindaian bisa disematkan; berkas PDF tidak (dompdf butuh
        // gambar). Pemakaian TTD di luar Kepsek dibahas di T6.3.
        $mime = $signature->file_path !== '' ? (string) pathinfo($signature->file_path, PATHINFO_EXTENSION) : '';

        if (in_array(strtolower($mime), ['png', 'jpg', 'jpeg'], true)) {
            $absolute = Storage::disk('local')->path($signature->file_path);

            if (is_file($absolute)) {
                $image = 'data:image/'.($mime === 'png' ? 'png' : 'jpeg').';base64,'.base64_encode((string) file_get_contents($absolute));
            }
        }

        return [
            'nama' => $owner?->name,
            'nip' => $owner?->nis_nip,
            'jabatan' => __('pkl.document.kepsek_jabatan'),
            'image' => $image,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function pengantarData(Model $document): array
    {
        if (! $document instanceof PengajuanPkl) {
            throw new RuntimeException('Dokumen surat pengantar bukan company group.');
        }

        $period = $document->academicPeriod;

        $siswa = $document->students()
            ->with('siswa.jurusan')
            ->whereIn('status', [
                'menunggu_surat_pengantar',
                'surat_pengantar_terbit',
                'menunggu_balasan_perusahaan',
                'sedang_pkl',
            ])
            ->get()
            ->map(fn ($row): array => [
                'nama' => $row->siswa?->name,
                'nis' => $row->siswa?->nis_nip,
                'komp_keahlian' => $row->siswa?->jurusan?->nama,
                'keterangan' => $row->tanggal_mulai_pkl !== null && $row->tanggal_selesai_pkl !== null
                    ? sprintf(
                        '%s s.d. %s',
                        JakartaTime::humanDate($row->tanggal_mulai_pkl),
                        JakartaTime::humanDate($row->tanggal_selesai_pkl),
                    )
                    : null,
            ])
            ->all();

        return [
            'company' => [
                'nama' => $document->company?->nama,
                'alamat' => $document->company?->alamat,
            ],
            'siswa' => $siswa,
            'periode' => $period === null ? null : [
                'mulai' => JakartaTime::monthYear($period->tanggal_mulai),
                'selesai' => JakartaTime::monthYear($period->tanggal_selesai),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function tugasPembimbingData(Model $document): array
    {
        if (! $document instanceof SuratTugasPembimbing) {
            throw new RuntimeException('Dokumen surat tugas pembimbing tidak sesuai.');
        }

        $plotting = $document->plotting;

        $siswa = [];

        if ($plotting !== null) {
            $group = PengajuanPkl::query()
                ->where('company_id', $plotting->company_id)
                ->where('academic_period_id', $plotting->academic_period_id)
                ->first();

            $siswa = $group === null ? [] : $group->students()
                ->with('siswa.kelas')
                ->where('status', 'sedang_pkl')
                ->get()
                ->map(fn ($row): array => [
                    'nama' => $row->siswa?->name,
                    // Skema `users` tidak punya kolom alamat; kolom tetap dicetak
                    // kosong seperti contoh fisik (dicatat di TASKS.md).
                    'alamat' => null,
                    'kelas' => $row->siswa?->kelas?->nama,
                    'keterangan' => null,
                ])
                ->all();
        }

        return [
            'guru' => [
                'nama' => $document->guru?->name,
                'nip' => $document->guru?->nis_nip,
            ],
            'company' => [
                'nama' => $plotting?->company?->nama,
                'alamat' => $plotting?->company?->alamat,
            ],
            'siswa' => $siswa,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function tugasVisitasiData(Model $document): array
    {
        if (! $document instanceof SuratTugasVisitasi) {
            throw new RuntimeException('Dokumen surat tugas visitasi tidak sesuai.');
        }

        $companies = $document->companies()
            ->with('company')
            ->get()
            ->map(fn ($row): array => [
                'nama' => $row->company?->nama,
                'alamat' => $row->company?->alamat,
            ])
            ->all();

        return [
            'guru' => [
                'nama' => $document->guru?->name,
                'nip' => $document->guru?->nis_nip,
            ],
            'is_general' => (bool) $document->is_general,
            'companies' => $companies,
            'masa_berlaku' => [
                'mulai' => JakartaTime::humanDate($document->tanggal_mulai),
                'selesai' => JakartaTime::humanDate($document->tanggal_selesai),
            ],
        ];
    }
}
