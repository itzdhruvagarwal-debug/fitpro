<?php

namespace Database\Factories\Concerns;

trait ForCurrentTenant
{
    protected function currentTenantId(): int|string|null
    {
        if (! app()->bound('currentTenant') || ! app('currentTenant')) {
            return null;
        }

        return app('currentTenant')->getKey();
    }
}
