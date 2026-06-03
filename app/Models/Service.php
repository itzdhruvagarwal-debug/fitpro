<?php

namespace App\Models;

use App\Models\Concerns\CascadesSoftDeletes;
use App\Traits\HasGym;
use Database\Factories\ServiceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Service extends Model
{
    /** @use HasFactory<ServiceFactory> */
    use CascadesSoftDeletes, HasFactory, SoftDeletes;

    use HasGym;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'gym_id',
        'name',
        'description',
    ];

    /** @var list<string> */
    protected $dates = ['deleted_at'];

    /**
     * Get the plans for the service.
     */
    /**
     * @return HasMany<Plan, $this>
     */
    public function plans(): HasMany
    {
        return $this->hasMany(Plan::class);
    }

    /**
     * Relationship method names to cascade when deleting/restoring.
     *
     * @return list<string>
     */
    protected static function relationsToCascade(): array
    {
        return ['plans'];
    }
}
