<?php

namespace App\Providers;

use App\Models\PengajuanPkl;
use App\Models\SuratTugasPembimbing;
use App\Models\SuratTugasVisitasi;
use App\Policies\DocumentPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Tiga baris dokumen berbagi satu Policy unduhan (T3.1). Model lain memakai
        // penemuan otomatis `App\Policies\{Model}Policy`.
        Gate::policy(PengajuanPkl::class, DocumentPolicy::class);
        Gate::policy(SuratTugasPembimbing::class, DocumentPolicy::class);
        Gate::policy(SuratTugasVisitasi::class, DocumentPolicy::class);
    }
}
