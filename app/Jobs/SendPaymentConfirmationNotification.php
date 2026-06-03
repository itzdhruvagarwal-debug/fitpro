<?php

namespace App\Jobs;

use App\Helpers\Helpers;
use App\Models\Member;
use App\Models\PaymentTransaction;
use App\Services\Msg91Service;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Spatie\Multitenancy\Jobs\TenantAware;

class SendPaymentConfirmationNotification implements ShouldBeUnique, ShouldQueue, TenantAware
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $uniqueFor = 86400;

    public function __construct(
        public readonly int $memberId,
        public readonly int $transactionId,
    ) {}

    public function uniqueId(): string
    {
        return "payment_confirm:txn:{$this->transactionId}";
    }

    public function handle(Msg91Service $msg91): void
    {
        $member = Member::query()->find($this->memberId);
        $txn = PaymentTransaction::query()->find($this->transactionId);

        if (! $member || ! $txn) {
            return;
        }

        $settings = Helpers::getSettings();
        if (! (bool) data_get($settings, 'notifications.whatsapp.enabled', false)) {
            return;
        }
        if (! (bool) data_get($settings, 'notifications.whatsapp.send_payment_confirmation', true)) {
            return;
        }
        if (! (bool) ($member->whatsapp_enabled ?? true)) {
            return;
        }

        $gymName = (string) data_get($settings, 'general.gym_name', 'Gym');
        $latestSubscription = $member->subscriptions()->latest('end_date')->with('plan')->first();
        $newExpiry = $latestSubscription?->end_date?->format('d-m-Y') ?? '';
        $invoiceNumber = $txn->razorpay_payment_id ?: ($txn->razorpay_order_id ?: (string) $txn->id);

        $waOk = $msg91->sendWhatsApp($member, 'payment_confirm', [
            (string) $member->name,
            (string) $txn->amount,
            (string) $invoiceNumber,
            (string) $newExpiry,
            $gymName,
        ]);

        if ($waOk) {
            return;
        }

        if (! (bool) data_get($settings, 'notifications.sms.enabled', false)) {
            $this->release(3600);

            return;
        }
        if (! (bool) ($member->sms_enabled ?? true)) {
            return;
        }

        $sms = "Payment of Rs.{$txn->amount} received. Invoice: {$invoiceNumber}. Membership valid till {$newExpiry}.";
        $smsOk = $msg91->sendSms($member, $sms);

        if (! $smsOk) {
            $this->release(3600);
        }
    }
}
