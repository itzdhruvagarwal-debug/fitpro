<?php

namespace App\Models;

use App\Traits\HasGym;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberAttendance extends Model
{
    use HasFactory, HasGym;

    protected $fillable = [
        'gym_id',
        'member_id',
        'checked_in_at',
        'latitude',
        'longitude',
    ];

    protected $casts = [
        'checked_in_at' => 'datetime',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
    ];

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function gym(): BelongsTo
    {
        return $this->belongsTo(Gym::class);
    }
}
