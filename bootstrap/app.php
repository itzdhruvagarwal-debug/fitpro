<?php

use App\Http\Middleware\EnsureSuperAdminDomain;
use App\Http\Middleware\EnsureTenantIsActive;
use App\Http\Middleware\EnsureTenantUserAccess;
use App\Http\Middleware\ForceJsonResponse;
use App\Http\Middleware\SetAppLocale;
use App\Http\Middleware\SetPermissionsTeamFromTenant;
use App\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\Exceptions\InvalidFilterQuery;
use Spatie\QueryBuilder\Exceptions\InvalidIncludeQuery;
use Spatie\QueryBuilder\Exceptions\InvalidQuery;
use Spatie\QueryBuilder\Exceptions\InvalidSortQuery;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(prepend: [
            SetAppLocale::class,
        ], replace: [
            Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class => VerifyCsrfToken::class,
        ]);

        $middleware->api(prepend: [
            SetAppLocale::class,
            ForceJsonResponse::class,
        ]);

        $middleware->alias([
            'tenant.active' => EnsureTenantIsActive::class,
            'superadmin.domain' => EnsureSuperAdminDomain::class,
            'permissions.team' => SetPermissionsTeamFromTenant::class,
            'tenant.user' => EnsureTenantUserAccess::class,
            'auth.member' => \App\Http\Middleware\AuthenticateMember::class,
            'guest.member' => \App\Http\Middleware\RedirectIfMember::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (InvalidQuery $exception, Request $request) {
            $errors = ['query' => [$exception->getMessage()]];

            if ($exception instanceof InvalidFilterQuery) {
                $errors = ['filter' => [$exception->getMessage()]];
            } elseif ($exception instanceof InvalidIncludeQuery) {
                $errors = ['include' => [$exception->getMessage()]];
            } elseif ($exception instanceof InvalidSortQuery) {
                $errors = ['sort' => [$exception->getMessage()]];
            }

            return response()->json([
                'message' => __('app.api.invalid_query'),
                'errors' => $errors,
            ], 400);
        });
    })->create();
