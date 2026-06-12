<?php

namespace App\Jobs;

use App\Models\Gym;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessGymSubscriptionWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public readonly string $event,
        public readonly array $payload
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $payment = data_get($this->payload, 'payload.payment.entity', []);
        $subscription = data_get($this->payload, 'payload.subscription.entity', []);

        $gymId = (int) (data_get($payment, 'notes.gym_id') ?: data_get($subscription, 'notes.gym_id'));
        if (! $gymId) {
            Log::warning('ProcessGymSubscriptionWebhook: gym_id not found in payload notes.');
            return;
        }

        $gym = Gym::query()->withoutGlobalScopes()->find($gymId);
        if (! $gym) {
            Log::warning("ProcessGymSubscriptionWebhook: Gym [{$gymId}] not found.");
            return;
        }

        $planName = (string) (data_get($payment, 'notes.plan_name') 
            ?: data_get($subscription, 'notes.plan_name') 
            ?: 'starter');

        switch ($this->event) {
            case 'subscription.activated':
                $gym->update([
                    'plan' => $planName,
                    'status' => 'active',
                    'razorpay_subscription_id' => data_get($subscription, 'id'),
                    'razorpay_mandate_status' => 'active',
                ]);
                break;

            case 'payment.captured':
            case 'subscription.charged':
                $paymentId = data_get($payment, 'id');
                $amount = ((int) data_get($payment, 'amount', 0)) / 100;

                // Log the payment
                DB::table('gym_subscription_payments')->updateOrInsert(
                    ['razorpay_payment_id' => $paymentId],
                    [
                        'gym_id' => $gym->id,
                        'razorpay_order_id' => data_get($payment, 'order_id'),
                        'razorpay_subscription_id' => data_get($payment, 'subscription_id') ?: data_get($subscription, 'id'),
                        'amount' => $amount,
                        'plan' => $planName,
                        'status' => 'captured',
                        'paid_at' => data_get($payment, 'captured_at')
                            ? Carbon::createFromTimestamp((int) data_get($payment, 'captured_at'))
                            : now(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );

                // Extend plan expiration by 30 days
                $currentExpiry = $gym->plan_expires_at;
                $newExpiry = ($currentExpiry && $currentExpiry->isFuture())
                    ? $currentExpiry->addDays(30)
                    : now()->addDays(30);

                $gym->update([
                    'plan' => $planName,
                    'status' => 'active',
                    'plan_expires_at' => $newExpiry,
                ]);
                break;

            case 'subscription.halted':
                $gym->update([
                    'razorpay_mandate_status' => 'halted',
                    'status' => 'suspended',
                ]);
                break;

            case 'subscription.cancelled':
                $gym->update([
                    'razorpay_mandate_status' => 'cancelled',
                    'status' => 'suspended',
                ]);
                break;
        }
    }
}
