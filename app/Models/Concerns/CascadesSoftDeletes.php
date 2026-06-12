<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Model;

/**
 * Cascade soft deletes and restores for configured relationships.
 *
 * Models using this trait must also use `SoftDeletes`, and should override
 * {@see self::relationsToCascade()} to return relationship method names.
 */
trait CascadesSoftDeletes
{
    /**
     * Relationship method names to cascade.
     *
     * @return list<string>
     */
    protected static function relationsToCascade(): array
    {
        return [];
    }

    /**
     * Register cascade callbacks for Eloquent model events.
     */
    protected static function bootCascadesSoftDeletes(): void
    {
        static::deleting(function (Model $model): void {
            foreach (static::relationsToCascade() as $relation) {
                $model->{$relation}()->update(['deleted_at' => now()]);
            }
        });

        static::restoring(function (Model $model): void {
            foreach (static::relationsToCascade() as $relation) {
                $model->{$relation}()->withTrashed()->update(['deleted_at' => null]);
            }
        });
    }
}
