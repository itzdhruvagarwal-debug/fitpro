<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpFoundation\Response;

class SetPermissionsTeamFromTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var PermissionRegistrar $registrar */
        $registrar = app(PermissionRegistrar::class);

        if (app()->bound('currentTenant') && app('currentTenant')) {
            $registrar->setPermissionsTeamId((int) app('currentTenant')->id);
        } else {
            $registrar->setPermissionsTeamId(null);
        }

        return $next($request);
    }
}
