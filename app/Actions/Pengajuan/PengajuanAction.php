<?php

namespace App\Actions\Pengajuan;

use App\Actions\Action;
use App\Enums\PengajuanStatus;
use App\Models\PengajuanSiswa;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/**
 * Dasar Action pengajuan (F3). Setiap transisi memakai tabel transisi Enum dan
 * memeriksa izin aktor lewat Policy, sehingga UI, tes, dan pemanggil lain mendapat
 * aturan yang sama. Catatan penolakan ≥ 10 karakter divalidasi di sini, bukan di UI.
 */
abstract class PengajuanAction extends Action
{
    protected function authorize(string $ability, mixed $arguments, User $actor): void
    {
        Gate::forUser($actor)->authorize($ability, $arguments);
    }

    /**
     * Pastikan pasangan transisi dikenal tabel sebelum memeriksa izin dan menulis.
     * Pasangan yang tidak ada → 409; aktor yang tidak berhak → 403.
     */
    protected function assertTransitionable(PengajuanSiswa $pengajuan, PengajuanStatus $to, string $actor): void
    {
        $from = PengajuanStatus::from((string) $pengajuan->getAttribute('status'));

        if ($from !== $to) {
            $from->assertTransitionTo($to, $actor);
        }
    }

    /**
     * Transisi idempoten: status sumber diperiksa di dalam transaksi. Panggilan kedua
     * mengembalikan 0 tanpa efek (TRD.md §8).
     */
    protected function transition(PengajuanSiswa $pengajuan, PengajuanStatus $to, string $actor, array $attributes = []): int
    {
        $from = PengajuanStatus::from((string) $pengajuan->getAttribute('status'));

        if ($from === $to) {
            return 0;
        }

        $from->assertTransitionTo($to, $actor);

        return $this->transitionStatus($pengajuan, $to->value, $from->value, $attributes);
    }

    protected function assertRejectionNote(?string $note): string
    {
        $note = trim((string) $note);
        $min = (int) config('pkl.rejection_note.min_length');

        if (mb_strlen($note) < $min) {
            throw ValidationException::withMessages([
                'catatan' => __('pkl.rejection_note.min', ['min' => $min]),
            ]);
        }

        return $note;
    }
}
