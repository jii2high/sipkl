<?php

use App\Providers\AppServiceProvider;
use App\Providers\Filament\GuruPanelProvider;
use App\Providers\Filament\HubinPanelProvider;
use App\Providers\Filament\KaprogPanelProvider;

return [
    AppServiceProvider::class,
    GuruPanelProvider::class,
    HubinPanelProvider::class,
    KaprogPanelProvider::class,
];
