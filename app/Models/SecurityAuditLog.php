<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\MassPrunable;

/**
 * @property int $id
 * @property int|null $gym_id
 * @property int|null $user_id
 * @property string $event_type
 * @property string|null $email
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property string $outcome
 * @property array<string, mixed>|null $context
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class SecurityAuditLog extends Model
{
    use MassPrunable;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'security_audit_logs';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'gym_id',
        'user_id',
        'event_type',
        'email',
        'ip_address',
        'user_agent',
        'outcome',
        'context',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'context' => 'array',
    ];

    /**
     * Get the prunable model query.
     */
    public function prunable()
    {
        return static::where('created_at', '<=', now()->subDays(90));
    }
}
