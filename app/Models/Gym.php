<?php

namespace App\Models;

use Spatie\Multitenancy\Models\Tenant;

class Gym extends Tenant
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'domain',
        'owner_name',
        'owner_email',
        'owner_phone',
        'gstin',
        'address',
        'city',
        'state',
        'pincode',
        'logo_path',
        'plan',
        'plan_expires_at',
        'status',
        'settings',
        'trial_ends_at',
        'database',
    ];

    protected $casts = [
        'settings' => 'array',
        'plan_expires_at' => 'datetime',
        'trial_ends_at' => 'datetime',
    ];

    public function getDomainAttribute(): string
    {
        return $this->slug.'.'.config('app.base_domain', 'gymsaathi.in');
    }

    public function isOnTrial(): bool
    {
        return (bool) ($this->trial_ends_at && $this->trial_ends_at->isFuture());
    }

    public function isActive(): bool
    {
        return $this->status === 'active'
            && ($this->plan_expires_at === null || $this->plan_expires_at->isFuture());
    }
}
