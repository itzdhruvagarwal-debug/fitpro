<?php

namespace App\Models;

use App\Traits\HasGym;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $member_id
 * @property string|null $razorpay_payment_id
 * @property string|null $razorpay_order_id
 * @property string|null $razorpay_subscription_id
 * @property float|int|string $amount
 * @property string $currency
 * @property string $status
 * @property string|null $payment_method
 * @property string|null $description
 * @property array<string, mixed>|null $metadata
 * @property Carbon|null $paid_at
 * @property-read Member $member
 */
class PaymentTransaction extends Model
{
    /** @use HasFactory<Factory<static>> */
    use HasFactory;

    use HasGym;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'gym_id',
        'member_id',
        'razorpay_payment_id',
        'razorpay_order_id',
        'razorpay_subscription_id',
        'amount',
        'currency',
        'status',
        'payment_method',
        'description',
        'metadata',
        'paid_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'metadata' => 'array',
        'paid_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<Member, $this>
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class)->withTrashed();
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeCaptured(Builder $query): Builder
    {
        return $query->where('status', 'captured');
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('status', 'failed');
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeThisMonth(Builder $query): Builder
    {
        return $query
            ->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month);
    }
}
