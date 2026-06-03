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

class SendExpiryWarningNotification implements ShouldBeUnique, ShouldQueue, TenantAware
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $uniqueFor = 86400;

    public function __construct(
        public readonly int $memberId,
        public readonly string $forDate,
    ) {}

    public function uniqueId(): string
    {
        return "expiry_warning:member:{$this->memberId}:{$this->forDate}";
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
        if (! (bool) data_get($settings, 'notifications.whatsapp.send_expiry_warning', true)) {
            return;
        }
        if (! (bool) ($member->whatsapp_enabled ?? true)) {
            return;
        }

        $latestSubscription = $member->subscriptions()->latest('end_date')->with('plan')->first();
        $gymName = (string) data_get($settings, 'general.gym_name', 'Gym');
        $planName = (string) ($latestSubscription?->plan?->name ?? 'Membership');

        // NOTE: No public renewal link exists yet; keep blank for now.
        $renewalLink = '';

        $waOk = $msg91->sendWhatsApp($member, 'expiry_warning', [
            (string) $member->name,
            $gymName,
            $planName,
            $renewalLink,
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

        $sms = "Dear {$member->name}, your {$planName} membership at {$gymName} has expired today. Renew now: {$renewalLink}";
        $smsOk = $msg91->sendSms($member, $sms);

        if (! $smsOk) {
            $this->release(3600);
        }
    }
}
