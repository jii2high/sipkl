<?php

namespace App\Policies;

use App\Models\GuruProfile;
use App\Models\IndikatorPenilaian;
use App\Models\KompetensiKeahlian;
use App\Models\PeriodePkl;
use App\Models\Perusahaan;
use App\Models\SectionPenilaian;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class HubinPolicy
{
    private static array $permissionMap = [
        Perusahaan::class => 'master-perusahaan.kelola',
        PeriodePkl::class => 'master-periode-pkl.kelola',
        KompetensiKeahlian::class => 'master-kompetensi-keahlian.kelola',
        IndikatorPenilaian::class => 'master-indikator-penilaian.kelola',
        SectionPenilaian::class => 'master-section-penilaian.kelola',
        GuruProfile::class => 'master-akun.kelola',
    ];

    public function kelola(User $user, mixed $model = null): bool|Response
    {
        $resource = null;

        if (is_string($model)) {
            $resource = $model;
        } elseif (is_object($model)) {
            $resource = $model::class;
        }

        $permission = self::$permissionMap[$resource] ?? 'master-akun.kelola';

        return $user->hasPermissionTo($permission);
    }
}
