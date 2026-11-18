<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * Gerbang tunggal penyimpanan lampiran (TRD.md §16, BR-F12-5). Batas ukuran, daftar
 * ekstensi, dan jenis MIME per kegunaan dibaca dari `config/pkl.php`; MIME dideteksi
 * dari **isi** berkas (bukan ekstensi atau klaim klien), nama berkas diacak, dan
 * semuanya masuk disk privat `local`. Dipakai ulang oleh bimbingan, surat balasan,
 * dokumentasi visitasi, dan scan TTD Kepsek.
 */
final class FileStorageService
{
    /** Kegunaan → basis konfigurasi (max_mb, extensions, mimes). */
    private const KINDS = [
        'bimbingan' => 'pkl.bimbingan.upload',
        'visitasi' => 'pkl.visitasi.upload',
        'signature' => 'pkl.signature',
        'surat_balasan' => 'pkl.surat_balasan',
    ];

    /**
     * Periksa lalu simpan berkas pada disk privat. Nama berkas dibuat acak oleh
     * penyimpanan, sehingga nama asli tidak pernah dipakai sebagai path.
     */
    public function store(UploadedFile $file, string $kind, ?string $folder = null): string
    {
        $this->assertAcceptable($file, $kind);

        $path = Storage::disk('local')->putFile($folder ?? $kind, $file);

        if ($path === false) {
            throw new RuntimeException('Berkas gagal disimpan.');
        }

        return $path;
    }

    /** Hapus berkas privat; berkas yang sudah tidak ada dianggap selesai. */
    public function delete(?string $path): void
    {
        if ($path !== null && $path !== '') {
            Storage::disk('local')->delete($path);
        }
    }

    /**
     * Validasi ukuran, ekstensi, dan MIME dari isi berkas. Melempar
     * `ValidationException` dengan pesan Bahasa Indonesia (BR-F12-5).
     */
    public function assertAcceptable(UploadedFile $file, string $kind): void
    {
        $rules = $this->rules($kind);
        $extensions = array_map('strtolower', (array) $rules['extensions']);
        $mimes = array_map('strtolower', (array) $rules['mimes']);

        $extension = strtolower($file->getClientOriginalExtension() ?: (string) $file->extension());

        if (! in_array($extension, $extensions, true)) {
            throw ValidationException::withMessages([
                'file' => __('pkl.upload.mimes', ['types' => implode(', ', $extensions)]),
            ]);
        }

        if ($file->getSize() > (int) $rules['max_mb'] * 1024 * 1024) {
            throw ValidationException::withMessages([
                'file' => __('pkl.upload.max', ['max' => (int) $rules['max_mb']]),
            ]);
        }

        if (! in_array($this->detectMime($file), $mimes, true)) {
            throw ValidationException::withMessages([
                'file' => __('pkl.upload.mime_mismatch'),
            ]);
        }
    }

    /** MIME sebenarnya, dibaca dari isi berkas. */
    public function detectMime(UploadedFile $file): string
    {
        $path = $file->getRealPath();

        if ($path === false) {
            return '';
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);

        return (string) ($finfo->file($path) ?: '');
    }

    /**
     * @return array{max_mb: int, extensions: array<int, string>, mimes: array<int, string>}
     */
    private function rules(string $kind): array
    {
        $base = self::KINDS[$kind] ?? null;

        if ($base === null) {
            throw new RuntimeException("Jenis unggahan tidak dikenal: {$kind}.");
        }

        $rules = config($base);

        if (! is_array($rules) || ! isset($rules['max_mb'], $rules['extensions'], $rules['mimes'])) {
            throw new RuntimeException("Aturan unggahan `{$kind}` belum lengkap di config/pkl.php.");
        }

        return [
            'max_mb' => (int) $rules['max_mb'],
            'extensions' => (array) $rules['extensions'],
            'mimes' => (array) $rules['mimes'],
        ];
    }
}
