<?php

namespace App\Permissions;

use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Contracts\PermissionsTeamResolver;

class GymPermissionsTeamResolver implements PermissionsTeamResolver
{
    protected int|string|null $teamId = null;

    public function setPermissionsTeamId($id): void
    {
        $this->teamId = $id;
    }

    public function getPermissionsTeamId(): int|string|null
    {
        if ($this->teamId !== null) {
            return $this->teamId;
        }

        if (app()->bound('currentTenant') && app('currentTenant')) {
            return app('currentTenant')->id;
        }

        $userGymId = Auth::user()?->gym_id;

        if ($userGymId !== null) {
            return $userGymId;
        }

        return null;
    }
}
