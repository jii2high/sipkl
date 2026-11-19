<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\PengajuanPkl;
use App\Models\SuratTugasPembimbing;
use App\Models\SuratTugasVisitasi;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Akses berkas dokumen (TRD.md §7.4). Unduhan selalu lewat controller berotorisasi;
 * Hubin boleh semua dokumen, guru hanya surat tugas miliknya. Siswa/Kepsek mendapat
 * akses pada task yang membuat layarnya (T4.2/T6.3).
 */
class DocumentPolicy extends Policy
{
    public function download(User $user, Model $document): bool
    {
        if ($this->hasActiveRole($user, Role::Hubin->value)) {
            return true;
        }

        if ($document instanceof PengajuanPkl && $this->hasActiveRole($user, Role::Siswa->value)) {
            return $document->students()->where('siswa_id', $user->getKey())->exists();
        }

        if (! $this->hasActiveRole($user, Role::GuruPembimbing->value)) {
            return false;
        }

        if ($document instanceof SuratTugasPembimbing) {
            return $document->plotting?->guru_id === $user->getKey();
        }

        if ($document instanceof SuratTugasVisitasi) {
            return $document->guru_id === $user->getKey();
        }

        // Surat pengantar (company group) belum punya pemilik per guru/siswa di T3.1.
        return false;
    }

    /** Menerbitkan surat pengantar per company group (T4.2). */
    public function issueLetter(User $user, PengajuanPkl $group): bool
    {
        return $this->hasActiveRole($user, Role::Hubin->value)
            && $group->status === 'terbentuk';
    }
}
