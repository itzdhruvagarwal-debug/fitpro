<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSuperAdminDomain
{
    public function handle(Request $request, Closure $next): Response
    {
        $host = $request->getHost();
        $base = (string) config('app.base_domain', 'gymsaathi.in');
        $allowed = [$base, 'admin.'.$base, 'localhost', '127.0.0.1'];

        if (! in_array($host, $allowed, true)) {
            abort(404);
        }

        return $next($request);
    }
}
