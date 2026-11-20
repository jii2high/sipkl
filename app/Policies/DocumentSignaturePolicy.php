<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\DocumentSignature;
use App\Models\User;

/**
 * Otorisasi tanda tangan laporan (F7). Step 2 milik Kaprog pada jurusan siswa;
 * step 5 milik Kepsek; step 1 dibuat Guru lewat `ApproveLaporanFinal`; step 3/4
 * hanya lewat magic link. Tautan step 3/4 diterbitkan Kaprog atau Hubin.
 */
class DocumentSignaturePolicy extends Policy
{
    public function sign(User $user, DocumentSignature $signature): bool
    {
        $pengajuan = $signature->pengajuanSiswa;

        if ($pengajuan === null) {
            return false;
        }

        return match ((int) $signature->step_order) {
            2 => $this->hasActiveRole($user, Role::Kaprog->value)
                && (int) $pengajuan->siswa?->jurusan_id === (int) $user->roleScopeId(Role::Kaprog),
            5 => $this->hasActiveRole($user, Role::Kepsek->value),
            default => false,
        };
    }

    public function reject(User $user, DocumentSignature $signature): bool
    {
        return $this->sign($user, $signature);
    }

    public function issueLink(User $user, DocumentSignature $signature): bool
    {
        if (! in_array((int) $signature->step_order, [3, 4], true)) {
            return false;
        }

        if ($this->hasActiveRole($user, Role::Hubin->value)) {
            return true;
        }

        $pengajuan = $signature->pengajuanSiswa;

        return $pengajuan !== null
            && $this->hasActiveRole($user, Role::Kaprog->value)
            && (int) $pengajuan->siswa?->jurusan_id === (int) $user->roleScopeId(Role::Kaprog);
    }
}
