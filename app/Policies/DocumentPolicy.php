<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Company;
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

        if ($document instanceof SuratTugasPembimbing
            && $this->hasActiveRole($user, Role::Kaprog->value)) {
            return (int) $document->plotting?->plotted_by === (int) $user->getKey();
        }

        if ($document instanceof SuratTugasVisitasi
            && $this->hasActiveRole($user, Role::Kaprog->value)) {
            return (int) $document->kaprog_id === (int) $user->getKey();
        }

        if ($document instanceof PengajuanPkl && $this->hasActiveRole($user, Role::Siswa->value)) {
            return $document->students()->where('siswa_id', $user->getKey())->exists();
        }

        if (! $this->hasActiveRole($user, Role::GuruPembimbing->value)) {
            return false;
        }

        if ($document instanceof SuratTugasPembimbing) {
            return (int) $document->guru_id === (int) $user->getKey();
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

    public function viewAny(User $user): bool
    {
        return $this->hasActiveRole($user, Role::Kaprog->value, Role::Hubin->value, Role::GuruPembimbing->value);
    }

    public function view(User $user, SuratTugasVisitasi $visitasi): bool
    {
        return match ($this->activeRole($user)) {
            Role::Hubin->value => true,
            Role::Kaprog->value => (int) $visitasi->kaprog_id === (int) $user->getKey(),
            Role::GuruPembimbing->value => (int) $visitasi->guru_id === (int) $user->getKey(),
            default => false,
        };
    }

    public function create(User $user): bool
    {
        return $this->hasActiveRole($user, Role::Kaprog->value, Role::Hubin->value);
    }

    public function approve(User $user, SuratTugasVisitasi $visitasi): bool
    {
        return $this->hasActiveRole($user, Role::Hubin->value)
            && $visitasi->status === 'diajukan';
    }

    public function issue(User $user, SuratTugasVisitasi $visitasi): bool
    {
        return $this->hasActiveRole($user, Role::Hubin->value)
            && in_array($visitasi->status, ['disetujui', 'dicetak', 'aktif'], true);
    }

    public function reject(User $user, SuratTugasVisitasi $visitasi): bool
    {
        return $this->approve($user, $visitasi);
    }

    public function upload(User $user, SuratTugasVisitasi $visitasi): bool
    {
        return $this->hasActiveRole($user, Role::GuruPembimbing->value)
            && (int) $visitasi->guru_id === (int) $user->getKey()
            && in_array($visitasi->status, ['disetujui', 'dicetak', 'aktif'], true);
    }
}
