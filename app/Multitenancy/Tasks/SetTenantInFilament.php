<?php

namespace App\Multitenancy\Tasks;

use Spatie\Multitenancy\Contracts\IsTenant;
use Spatie\Multitenancy\Tasks\SwitchTenantTask;

class SetTenantInFilament implements SwitchTenantTask
{
    public function makeCurrent(IsTenant $tenant): void
    {
        app()->instance('currentTenant', $tenant);
        config(['app.name' => $tenant->name]);
    }

    public function forgetCurrent(): void
    {
        if (app()->bound('currentTenant')) {
            app()->forgetInstance('currentTenant');
        }
    }
}
