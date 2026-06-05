<?php

namespace App\Support\Tenancy;

use Illuminate\Database\Eloquent\Builder;
use Spatie\Permission\Models\Role;

final class TenantRoleQuery
{
    /**
     * Restrict a role query to global roles plus roles owned by the active tenant.
     *
     * @param  Builder<Role>  $query
     * @return Builder<Role>
     */
    public static function apply(Builder $query, ?int $tenantId = null): Builder
    {
        $tenantId ??= app()->bound('currentTenant') && app('currentTenant')
            ? (int) app('currentTenant')->id
            : null;

        $teamKey = (string) config('permission.column_names.team_foreign_key', 'gym_id');

        return $query
            ->where('guard_name', 'web')
            ->where(function (Builder $query) use ($teamKey, $tenantId): void {
                $query->whereNull($teamKey);

                if ($tenantId !== null) {
                    $query->orWhere($teamKey, $tenantId);
                }
            });
    }
}
