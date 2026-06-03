<?php

namespace App\Traits;

use App\Models\Gym;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait HasGym
{
    public static function bootHasGym(): void
    {
        static::addGlobalScope('gym', function (Builder $builder): void {
            if (app()->bound('currentTenant') && app('currentTenant')) {
                $builder->where((new static)->getTable().'.gym_id', app('currentTenant')->id);
            }
        });

        static::creating(function ($model): void {
            if (! app()->bound('currentTenant') || ! app('currentTenant')) {
                return;
            }

            $currentGymId = app('currentTenant')->id;

            // Only set if missing.
            if (blank($model->getAttribute('gym_id'))) {
                $model->setAttribute('gym_id', $currentGymId);
            }
        });
    }

    /**
     * @return BelongsTo<Gym, $this>
     */
    public function gym(): BelongsTo
    {
        return $this->belongsTo(Gym::class);
    }
}
