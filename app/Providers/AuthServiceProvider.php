<?php

namespace App\Providers;

use App\Models\GuruProfile;
use App\Models\IndikatorPenilaian;
use App\Models\KompetensiKeahlian;
use App\Models\PeriodePkl;
use App\Models\Perusahaan;
use App\Models\SectionPenilaian;
use App\Policies\HubinPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Perusahaan::class => HubinPolicy::class,
        PeriodePkl::class => HubinPolicy::class,
        KompetensiKeahlian::class => HubinPolicy::class,
        IndikatorPenilaian::class => HubinPolicy::class,
        SectionPenilaian::class => HubinPolicy::class,
        GuruProfile::class => HubinPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();
    }
}
