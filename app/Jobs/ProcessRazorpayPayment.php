<?php

namespace App\Jobs;

use App\Models\Member;
use App\Models\PaymentTransaction;
use App\Services\GstInvoiceService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Spatie\Multitenancy\Jobs\TenantAware;

class ProcessRazorpayPayment implements ShouldQueue, TenantAware
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

        if ($paymentId === '') {
            return;
        }

        $existing = PaymentTransaction::query()
            ->where('razorpay_payment_id', $paymentId)
            ->first();

        if ($existing && $existing->status === 'captured') {
            return;
        }

        $member = $this->resolveMember($payment);
        if (! $member) {
            Log::warning('Razorpay payment processing skipped: member not found.', [
                'payment_id' => $paymentId,
                'status' => data_get($payment, 'status'),
            ]);

            return;
        }

        $transaction = PaymentTransaction::query()->updateOrCreate(
            ['razorpay_payment_id' => $paymentId],
            [
                'member_id' => $member->id,
                'razorpay_order_id' => data_get($payment, 'order_id'),
                'razorpay_subscription_id' => data_get($payment, 'subscription_id'),
                'amount' => ((int) data_get($payment, 'amount', 0)) / 100,
                'currency' => (string) data_get($payment, 'currency', 'INR'),
                'status' => 'captured',
                'payment_method' => data_get($payment, 'method'),
                'description' => 'Razorpay captured payment',
                'metadata' => [
                    'event' => data_get($this->payload, 'event'),
                    'notes' => data_get($payment, 'notes', []),
                ],
                'paid_at' => data_get($payment, 'captured_at')
                    ? Carbon::createFromTimestamp((int) data_get($payment, 'captured_at'))
                    : now(),
            ],
        );

        $latestPlanName = (string) ($member->subscriptions()->latest('end_date')->with('plan')->first()?->plan?->name ?? 'Membership');
        try {
            app(GstInvoiceService::class)->createGstInvoice(
                $member,
                ((int) data_get($payment, 'amount', 0)) / 100,
                'Membership renewal - '.$latestPlanName
            );
        } catch (\Throwable) {
            Log::warning('GST invoice generation failed after payment capture.', [
                'payment_id' => $paymentId,
                'status' => data_get($payment, 'status'),
            ]);
        }

        $member->extendMembership($this->resolveExtensionDays($member));

        if ($transaction?->getKey()) {
            SendPaymentConfirmationNotification::dispatch(
                memberId: (int) $member->getKey(),
                transactionId: (int) $transaction->getKey(),
            );
        }
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

    private function resolveExtensionDays(Member $member): int
    {
        $subscription = $member->subscriptions()->latest('end_date')->with('plan')->first();

        return (int) ($subscription?->plan?->days ?? 30);
    }
}
