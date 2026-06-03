<?php

namespace App\Services;

use App\Models\Member;
use App\Models\PaymentTransaction;
use App\Models\Plan;
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
        $period = $plan->days && (int) $plan->days > 60 ? 'yearly' : 'monthly';

        $rzpPlan = $this->api->plan->create([
            'period' => $period,
            'interval' => 1,
            'item' => [
                'name' => $plan->name.' - '.config('app.name'),
                'amount' => (int) round(((float) $plan->amount) * 100),
                'currency' => 'INR',
            ],
            'notes' => [
                'local_plan_id' => (string) $plan->id,
            ],
        ]);

        $subscription = $this->api->subscription->create([
            'plan_id' => $rzpPlan->id,
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
}
