<?php

use App\Http\Middleware\SetAppLocale;
use App\Providers\Filament\GymPanelProvider;
use Filament\Panel;

it('includes the locale middleware in the admin panel stack', function (): void {
    $provider = new GymPanelProvider(app());

    $panel = $provider->panel(Panel::make());

    expect($panel->getMiddleware())->toContain(SetAppLocale::class);
});
