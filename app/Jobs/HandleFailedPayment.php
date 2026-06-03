<?php

namespace App\Jobs;

use App\Models\Member;
use App\Models\PaymentTransaction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Spatie\Multitenancy\Jobs\TenantAware;

class HandleFailedPayment implements ShouldQueue, TenantAware
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(public readonly array $payload) {}

    public function handle(): void
    {
        $payment = data_get($this->payload, 'payload.payment.entity', []);
        $paymentId = (string) data_get($payment, 'id', '');
        $orderId = (string) data_get($payment, 'order_id', '');

        if ($paymentId === '' && $orderId === '') {
            return;
        }

        $member = $this->resolveMember($payment);
        if (! $member) {
            Log::warning('Razorpay failed payment could not resolve member.', [
                'payment_id' => $paymentId,
                'status' => data_get($payment, 'status'),
            ]);

            return;
        }

        $lookup = $paymentId !== ''
            ? ['razorpay_payment_id' => $paymentId]
            : ['razorpay_order_id' => $orderId];

        $transaction = PaymentTransaction::query()->firstOrNew($lookup);

        if ($transaction->exists && $transaction->status === 'captured') {
            return;
        }

        if ($paymentId !== '' && blank($transaction->razorpay_order_id) && $orderId !== '') {
            $transaction->razorpay_order_id = $orderId;
        }

        $transaction->fill([
            'member_id' => $member->id,
            'razorpay_payment_id' => $paymentId !== '' ? $paymentId : $transaction->razorpay_payment_id,
            'razorpay_order_id' => $orderId !== '' ? $orderId : $transaction->razorpay_order_id,
            'razorpay_subscription_id' => data_get($payment, 'subscription_id'),
            'amount' => ((int) data_get($payment, 'amount', 0)) / 100,
            'currency' => (string) data_get($payment, 'currency', 'INR'),
            'status' => 'failed',
            'payment_method' => data_get($payment, 'method'),
            'description' => 'Razorpay failed payment',
            'metadata' => [
                'event' => data_get($this->payload, 'event'),
                'error' => data_get($payment, 'error_description'),
            ],
        ]);
        $transaction->save();

        $reason = (string) (data_get($payment, 'error_description') ?: 'Payment failed');
        $amount = (string) (((int) data_get($payment, 'amount', 0)) / 100);

        SendPaymentFailedNotification::dispatch(
            memberId: (int) $member->getKey(),
            reason: $reason,
            amount: $amount,
        );
    }

    /**
     * @param  array<string, mixed>  $payment
     */
    private function resolveMember(array $payment): ?Member
    {
        $memberId = data_get($payment, 'notes.member_id');
        if (filled($memberId)) {
            return Member::query()->find($memberId);
        }

        $customerId = data_get($payment, 'customer_id');
        if (filled($customerId)) {
            return Member::query()->where('razorpay_customer_id', $customerId)->first();
        }

        $subscriptionId = data_get($payment, 'subscription_id');
        if (filled($subscriptionId)) {
            return Member::query()->where('razorpay_subscription_id', $subscriptionId)->first();
        }

        return null;
    }
}
