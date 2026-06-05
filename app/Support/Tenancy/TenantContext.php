<?php

namespace App\Support\Tenancy;

use App\Models\Gym;
use Spatie\Permission\PermissionRegistrar;

final class TenantContext
{
    private function __construct() {}

    /**
     * Run work with the given gym as the active tenant and permission team.
     *
     * @template TReturn
     *
     * @param  callable(): TReturn  $callback
     * @return TReturn
     */
    public static function run(Gym $tenant, callable $callback): mixed
    {
        /** @var Gym|null $previousTenant */
        $previousTenant = app()->bound('currentTenant') && app('currentTenant') instanceof Gym
            ? app('currentTenant')
            : null;

        /** @var PermissionRegistrar $registrar */
        $registrar = app(PermissionRegistrar::class);
        $previousTeamId = $registrar->getPermissionsTeamId();

        try {
            $tenant->makeCurrent();
            $registrar->setPermissionsTeamId((int) $tenant->id);

            return $callback();
        } finally {
            if ($previousTenant instanceof Gym) {
                $previousTenant->makeCurrent();
            } elseif (app()->bound('currentTenant')) {
                app()->forgetInstance('currentTenant');
            }

            $registrar->setPermissionsTeamId($previousTeamId);
        }
    }
}
