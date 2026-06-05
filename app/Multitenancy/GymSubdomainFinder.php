<?php

namespace App\Multitenancy;

use App\Models\Gym;
use Illuminate\Http\Request;
use Spatie\Multitenancy\TenantFinder\TenantFinder;

class GymSubdomainFinder extends TenantFinder
{
    public function findForRequest(Request $request): ?Gym
    {
        $host = (string) $request->getHost();
        $baseDomain = (string) config('app.base_domain', 'gymsaathi.in');

        if ($host === $baseDomain || $host === 'admin.'.$baseDomain) {
            return null;
        }

        if (! str_ends_with($host, '.'.$baseDomain)) {
            return null;
        }

        $subdomain = str_replace('.'.$baseDomain, '', $host);

        if (! filled($subdomain)) {
            return null;
        }

        return Gym::query()
            ->where('slug', $subdomain)
            ->first();
    }
}
