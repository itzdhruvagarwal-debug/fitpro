<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\V1\RoleResource;
use App\Support\Tenancy\TenantRoleQuery;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Spatie\Permission\Models\Role;

/**
 * Read-only roles listing (for permissions UI).
 */
class RolesController extends ApiController
{
    /**
     * List roles (requires `ViewAny:Role`).
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->requirePermission($request, 'ViewAny:Role');

        $roles = TenantRoleQuery::apply(Role::query())
            ->orderBy('name')
            ->with('permissions')
            ->get();

        return RoleResource::collection($roles);
    }
}
