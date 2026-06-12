<?php

use App\Providers\AppServiceProvider;
use App\Providers\Filament\GymPanelProvider;
use App\Providers\Filament\SuperAdminPanelProvider;

return [
    AppServiceProvider::class,
    GymPanelProvider::class,
    SuperAdminPanelProvider::class,
    App\Providers\HorizonServiceProvider::class,
];
