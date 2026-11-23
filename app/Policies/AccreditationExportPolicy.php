<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\AccreditationExport;
use App\Models\User;

/**
 * Unduhan ekspor akreditasi Hubin-only (KP-F13-2). Hanya attempt `completed`
 * yang belum kedaluwarsa dan benar-benar punya berkas yang boleh diunduh;
 * attempt `failed`/parsial tidak pernah dapat diunduh (KP-F13-1).
 */
class AccreditationExportPolicy extends Policy
{
    public function download(User $user, AccreditationExport $export): bool
    {
        return $this->hasActiveRole($user, Role::Hubin->value)
            && $export->status === 'completed'
            && is_string($export->snapshot_file_path)
            && $export->expires_at !== null
            && $export->expires_at->isFuture();
    }
}
