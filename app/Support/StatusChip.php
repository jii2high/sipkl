<?php

namespace App\Support;

/**
 * Pemetaan status domain ke chip Design.md: selalu pasangan (label Bahasa Indonesia,
 * tone warna). Status tidak pernah dibedakan warna saja, jadi chip selalu bertulisan.
 */
final class StatusChip
{
    /** @var array<string, array{0: string, 1: string}> */
    private const CHIPS = [
        // pengajuan_siswa.status (TRD.md §5.2)
        'draft' => ['Draf', 'info'],
        'menunggu_verifikasi_kaprog' => ['Menunggu verifikasi Kaprog', 'info'],
        'ditolak_kaprog' => ['Ditolak Kaprog', 'danger'],
        'menunggu_ttd_walas' => ['Menunggu TTD Walas', 'info'],
        'ditolak_walas' => ['Ditolak Walas', 'danger'],
        'menunggu_surat_pengantar' => ['Menunggu surat pengantar', 'info'],
        'surat_pengantar_terbit' => ['Surat pengantar terbit', 'success'],
        'menunggu_balasan_perusahaan' => ['Menunggu balasan perusahaan', 'info'],
        'rejected' => ['Ditolak', 'danger'],
        'sedang_pkl' => ['Sedang PKL', 'success'],
        'withdrawn' => ['Ditarik', 'neutral'],

        // pengajuan_pkl.status (company group)
        'terbentuk' => ['Terbentuk', 'info'],
        'surat_terbit' => ['Surat terbit', 'success'],
        'berjalan' => ['Berjalan', 'success'],
        'bubar' => ['Bubar', 'neutral'],

        // document_signatures.status
        'pending' => ['Menunggu TTD', 'warning'],
        'signed' => ['Sudah TTD', 'success'],

        // surat_tugas_visitasi.status
        'diajukan' => ['Diajukan', 'info'],
        'disetujui' => ['Disetujui', 'success'],
        'dicetak' => ['Dicetak', 'success'],
        'aktif' => ['Aktif', 'success'],
        'kedaluwarsa' => ['Kedaluwarsa', 'danger'],
        'ditolak' => ['Ditolak', 'danger'],

        // bimbingan_submissions.status
        'menunggu_feedback' => ['Menunggu feedback', 'info'],
        'revisi' => ['Perlu revisi', 'warning'],
        'diterima' => ['Diterima', 'success'],

        // form_definitions / accreditation_exports
        'published' => ['Terbit', 'success'],
        'queued' => ['Dalam antrean', 'info'],
        'running' => ['Diproses', 'info'],
        'completed' => ['Selesai', 'success'],
        'failed' => ['Gagal', 'danger'],
    ];

    public static function label(string $status): string
    {
        return self::CHIPS[$status][0] ?? self::humanize($status);
    }

    public static function tone(string $status): string
    {
        return self::CHIPS[$status][1] ?? 'neutral';
    }

    /** Tone Design.md → warna komponen Filament yang setara. */
    public static function color(string $status): string
    {
        return match (self::tone($status)) {
            'success' => 'success',
            'warning' => 'warning',
            'danger' => 'danger',
            'info' => 'info',
            default => 'gray',
        };
    }

    /** Status tanpa pemetaan tetap tampil terbaca, bukan kunci mentah. */
    private static function humanize(string $status): string
    {
        return ucfirst(str_replace('_', ' ', $status));
    }
}
