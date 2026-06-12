<?php

namespace App\Services;

use App\Models\Gym;
use App\Models\Member;
use App\Models\PaymentTransaction;
use App\Models\Plan;
use Illuminate\Support\Facades\DB;
use Razorpay\Api\Api;
use Throwable;

class RazorpayService
{
    protected Api $api;

    public function __construct()
    {
        $this->api = new Api(
            (string) config('razorpay.key_id'),
            (string) config('razorpay.key_secret'),
        );
    }

    public function getOrCreateCustomer(Member $member): string
    {
        if ($member->razorpay_customer_id) {
            return $member->razorpay_customer_id;
        }

        $customer = $this->api->customer->create([
            'name' => $member->name,
            'email' => $member->email,
            'contact' => $member->contact,
            'notes' => ['member_id' => $member->id],
        ]);

        $member->update(['razorpay_customer_id' => $customer->id]);

        return $customer->id;
    }

    /**
     * @return array{
     *  subscription_id:string,
     *  short_url:string|null,
     *  razorpay_key:string,
     *  customer_id:string
     * }
     */
    public function createSubscription(Member $member, Plan $plan): array
    {
        $customerId = $this->getOrCreateCustomer($member);
        $razorpayPlanId = $this->razorpayPlanIdFor($plan);

        $subscription = $this->api->subscription->create([
            'plan_id' => $razorpayPlanId,
            'customer_id' => $customerId,
            'total_count' => 120,
            'quantity' => 1,
            'customer_notify' => 1,
            'notes' => [
                'member_id' => (string) $member->id,
                'gym_name' => config('app.name'),
                'plan_name' => $plan->name,
            ],
        ]);

        $member->update([
            'razorpay_subscription_id' => $subscription->id,
            'upi_mandate_status' => 'pending',
        ]);

        return [
            'subscription_id' => $subscription->id,
            'short_url' => $subscription->short_url ?? null,
            'razorpay_key' => (string) config('razorpay.key_id'),
            'customer_id' => $customerId,
        ];
    }

    private function razorpayPlanIdFor(Plan $plan): string
    {
        return DB::transaction(function () use ($plan): string {
            /** @var Plan $lockedPlan */
            $lockedPlan = Plan::query()
                ->whereKey($plan->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if (filled($lockedPlan->razorpay_plan_id)) {
                return (string) $lockedPlan->razorpay_plan_id;
            }

            $period = $lockedPlan->days && (int) $lockedPlan->days > 60 ? 'yearly' : 'monthly';

            $rzpPlan = $this->api->plan->create([
                'period' => $period,
                'interval' => 1,
                'item' => [
                    'name' => $lockedPlan->name.' - '.config('app.name'),
                    'amount' => (int) round(((float) $lockedPlan->amount) * 100),
                    'currency' => 'INR',
                ],
                'notes' => [
                    'local_plan_id' => (string) $lockedPlan->id,
                ],
            ]);

            $lockedPlan->forceFill([
                'razorpay_plan_id' => (string) $rzpPlan->id,
            ])->save();

            return (string) $rzpPlan->id;
        });
    }

    /**
     * @return array{order_id:string,amount:int,currency:string,razorpay_key:string}
     */
    public function createOrder(Member $member, float $amount, string $description = ''): array
    {
        $order = $this->api->order->create([
            'amount' => (int) round($amount * 100),
            'currency' => 'INR',
            'receipt' => 'rcpt_'.$member->id.'_'.time(),
            'notes' => [
                'member_id' => (string) $member->id,
                'description' => $description,
            ],
        ]);

        PaymentTransaction::query()->create([
            'member_id' => $member->id,
            'razorpay_order_id' => $order->id,
            'amount' => $amount,
            'status' => 'created',
            'currency' => 'INR',
            'description' => $description,
        ]);

        return [
            'order_id' => $order->id,
            'amount' => (int) $order->amount,
            'currency' => (string) $order->currency,
            'razorpay_key' => (string) config('razorpay.key_id'),
        ];
    }

    public function verifyPaymentSignature(array $data): bool
    {
        try {
            $this->api->utility->verifyPaymentSignature($data);

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    public function verifyWebhookSignature(string $body, string $signature): bool
    {
        try {
            $this->api->utility->verifyWebhookSignature(
                $body,
                $signature,
                (string) config('razorpay.webhook_secret'),
            );

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    public function cancelSubscription(string $subscriptionId, bool $cancelAtCycleEnd = true): void
    {
        $this->api->subscription->fetch($subscriptionId)->cancel([
            'cancel_at_cycle_end' => $cancelAtCycleEnd ? 1 : 0,
        ]);
    }

    /**
     * Get or create a Razorpay Customer for a Gym.
     */
    public function getOrCreateGymCustomer(Gym $gym): string
    {
        $settings = $gym->settings ?? [];
        if (isset($settings['razorpay_customer_id']) && $settings['razorpay_customer_id']) {
            return (string) $settings['razorpay_customer_id'];
        }

        $customer = $this->api->customer->create([
            'name' => $gym->owner_name ?: $gym->name,
            'email' => $gym->owner_email ?: "billing-{$gym->slug}@gymsaathi.in",
            'contact' => $gym->owner_phone ?: '',
            'notes' => ['gym_id' => (string) $gym->id],
        ]);

        $settings['razorpay_customer_id'] = $customer->id;
        $gym->update(['settings' => $settings]);

        return (string) $customer->id;
    }

    /**
     * Get or create a Razorpay Plan for Gym subscriptions.
     */
    public function getOrCreateGymRazorpayPlan(string $planName): string
    {
        $amounts = [
            'starter' => 999.00,
            'growth' => 1999.00,
            'pro' => 3999.00,
        ];

        $amount = $amounts[$planName] ?? 999.00;

        return (string) \Illuminate\Support\Facades\Cache::rememberForever("razorpay_gym_plan_id_{$planName}", function () use ($planName, $amount) {
            $rzpPlan = $this->api->plan->create([
                'period' => 'monthly',
                'interval' => 1,
                'item' => [
                    'name' => ucfirst($planName).' Plan - GymSaathi Subscription',
                    'amount' => (int) round($amount * 100),
                    'currency' => 'INR',
                ],
                'notes' => [
                    'gym_plan_type' => $planName,
                ],
            ]);

            return (string) $rzpPlan->id;
        });
    }

    /**
     * Create a Razorpay Subscription for a Gym.
     *
     * @return array{
     *  subscription_id:string,
     *  short_url:string|null,
     *  razorpay_key:string,
     *  customer_id:string
     * }
     */
    public function createGymSubscription(Gym $gym, string $planName): array
    {
        $customerId = $this->getOrCreateGymCustomer($gym);
        $planId = $this->getOrCreateGymRazorpayPlan($planName);

        $subscription = $this->api->subscription->create([
            'plan_id' => $planId,
            'customer_id' => $customerId,
            'total_count' => 120, // 10 years
            'quantity' => 1,
            'customer_notify' => 1,
            'notes' => [
                'gym_id' => (string) $gym->id,
                'is_gym_subscription' => '1',
                'plan_name' => $planName,
            ],
        ]);

        return [
            'subscription_id' => $subscription->id,
            'short_url' => $subscription->short_url ?? null,
            'razorpay_key' => (string) config('razorpay.key_id'),
            'customer_id' => $customerId,
        ];
    }
}
