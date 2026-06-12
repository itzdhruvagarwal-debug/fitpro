<?php

namespace App\Models;

use App\Traits\HasGym;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $member_id
 * @property string $channel
 * @property string $template_name
 * @property string|null $phone
 * @property string|null $message_preview
 * @property array<string, mixed>|null $msg91_response
 * @property string $status
 * @property Carbon|null $sent_at
 * @property string|null $error_message
 * @property-read Member|null $member
 */
class NotificationLog extends Model
{
    /** @use HasFactory<Factory<static>> */
    use HasFactory;

    use HasGym;
    use MassPrunable;

    /**
     * Get the prunable model query.
     */
    public function prunable()
    {
        return static::where('created_at', '<=', now()->subDays(90));
    }

    /**
     * @var list<string>
     */
    protected $fillable = [
        'gym_id',
        'member_id',
        'channel',
        'template_name',
        'phone',
        'message_preview',
        'msg91_response',
        'status',
        'sent_at',
        'error_message',
    ];

    protected $casts = [
        'msg91_response' => 'array',
        'sent_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<Member, $this>
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }
}
