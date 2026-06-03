<?php

namespace App\Jobs;

use App\Helpers\Helpers;
use App\Models\Member;
use App\Services\Msg91Service;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Spatie\Multitenancy\Jobs\TenantAware;

class SendRenewalReminderNotification implements ShouldBeUnique, ShouldQueue, TenantAware
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $uniqueFor = 86400;

    public function __construct(
        public readonly int $memberId,
        public readonly int $daysLeft,
        public readonly string $forDate,
    ) {}

    public function uniqueId(): string
    {
        return "renewal_reminder:member:{$this->memberId}:{$this->daysLeft}:{$this->forDate}";
    }

    public function handle(Msg91Service $msg91): void
    {
        $member = Member::query()->find($this->memberId);
        if (! $member) {
            return;
        }

        $settings = Helpers::getSettings();

        if (! (bool) data_get($settings, 'notifications.whatsapp.enabled', false)) {
            return;
        }

        if (! (bool) data_get($settings, 'notifications.whatsapp.send_renewal_reminders', true)) {
            return;
        }

        if (! (bool) ($member->whatsapp_enabled ?? true)) {
            return;
        }

        $latestSubscription = $member->subscriptions()->latest('end_date')->with('plan')->first();
        $planName = (string) ($latestSubscription?->plan?->name ?? 'Membership');
        $amount = (string) ($latestSubscription?->plan?->amount ?? '');

        // NOTE: No public member payment link exists yet; keep blank for now.
        $paymentLink = '';

        $waOk = $msg91->sendWhatsApp($member, 'renewal_reminder', [
            (string) $member->name,
            (string) $this->daysLeft,
            $planName,
            $amount,
            $paymentLink,
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

        $sms = "Dear {$member->name}, your gym membership expires in {$this->daysLeft} days. Renew now: {$paymentLink}";
        $smsOk = $msg91->sendSms($member, $sms);

        if (! $smsOk) {
            $this->release(3600);
        }
    }
}
