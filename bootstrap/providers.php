<?php

use App\Providers\AppServiceProvider;
use App\Providers\Filament\AppPanelProvider;
use App\Providers\Filament\GuruPanelProvider;
use App\Providers\Filament\HubinPanelProvider;
use App\Providers\Filament\KaprogPanelProvider;

return [
    AppServiceProvider::class,
    AppPanelProvider::class,
    GuruPanelProvider::class,
    HubinPanelProvider::class,
    KaprogPanelProvider::class,
];
