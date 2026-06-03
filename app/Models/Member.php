<?php

namespace App\Models;

use App\Enums\Status;
use App\Helpers\Helpers;
use App\Models\Concerns\CascadesSoftDeletes;
use App\Support\AppConfig;
use App\Traits\HasGym;
use Carbon\Carbon;
use Database\Factories\MemberFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string|null $photo
 * @property string $code
 * @property string $name
 * @property string|null $email
 * @property string|null $gstin
 * @property string|null $contact
 * @property string|null $emergency_contact
 * @property string|null $health_issue
 * @property string|null $gender
 * @property \Illuminate\Support\Carbon|null $dob
 * @property string|null $address
 * @property string|null $country
 * @property string|null $state
 * @property string|null $city
 * @property string|null $pincode
 * @property string|null $source
 * @property string|null $goal
 * @property Status|null $status
 * @property-read Collection<int, Subscription> $subscriptions
 */
class Member extends Model
{
    /** @use HasFactory<MemberFactory> */
    use CascadesSoftDeletes, HasFactory, SoftDeletes;

    use HasGym;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'gym_id',
        'photo',
        'code',
        'name',
        'email',
        'gstin',
        'contact',
        'whatsapp_enabled',
        'sms_enabled',
        'notification_phone',
        'emergency_contact',
        'health_issue',
        'gender',
        'dob',
        'address',
        'country',
        'state',
        'city',
        'pincode',
        'source',
        'goal',
        'status',
        'razorpay_customer_id',
        'razorpay_subscription_id',
        'upi_autopay_active',
        'upi_mandate_status',
    ];

    protected $casts = [
        'dob' => 'date',
        'status' => Status::class,
        'upi_autopay_active' => 'boolean',
        'whatsapp_enabled' => 'boolean',
        'sms_enabled' => 'boolean',
    ];

    /**
     * The attributes that should be mutated to dates.
     * (SoftDeletes already adds deleted_at rollover.)
     *
     * @var list<string>
     */
    protected $dates = [
        'dob',
        'deleted_at',
    ];

    /**
     * Get the subscriptions for the member.
     */
    /**
     * @return HasMany<Subscription, $this>
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    /**
     * @return HasMany<PaymentTransaction, $this>
     */
    public function paymentTransactions(): HasMany
    {
        return $this->hasMany(PaymentTransaction::class);
    }

    public function isAutoPayActive(): bool
    {
        return (bool) $this->upi_autopay_active
            && $this->upi_mandate_status === 'active';
    }

    public function extendMembership(int $days): void
    {
        if ($days <= 0) {
            return;
        }

        $latestSubscription = $this->subscriptions()
            ->latest('end_date')
            ->first();

        if (! $latestSubscription) {
            return;
        }

        $timezone = AppConfig::timezone();
        $today = Carbon::today($timezone);
        $baseDate = $latestSubscription->end_date && $latestSubscription->end_date->isFuture()
            ? $latestSubscription->end_date
            : $today;

        $latestSubscription->update([
            'end_date' => $baseDate->copy()->addDays($days)->toDateString(),
            'status' => 'ongoing',
        ]);
    }

    /**
     * Boot the model and add cascade delete and restore behavior.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::saving(function (self $member): void {
            if (! $member->code) {
                $member->code = Helpers::generateLastNumber('member', Member::class, null, 'code');
            }
            Helpers::updateLastNumber('member', $member->code);
        });
    }

    /**
     * Relationship method names to cascade when deleting/restoring.
     *
     * @return list<string>
     */
    protected static function relationsToCascade(): array
    {
        return ['subscriptions'];
    }
}
