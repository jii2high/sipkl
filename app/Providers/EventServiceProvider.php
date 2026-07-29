<?php

namespace App\Providers;

use App\Listeners\CatatRiwayatPengajuan;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Spatie\ModelStates\Events\StateChanged;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        StateChanged::class => [
            CatatRiwayatPengajuan::class,
        ],
    ];
}
