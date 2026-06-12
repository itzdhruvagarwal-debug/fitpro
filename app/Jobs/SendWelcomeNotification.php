<?php

namespace App\Jobs;

use App\Helpers\Helpers;
use App\Models\Member;
use App\Services\Msg91Service;
use App\Jobs\Concerns\LogsJobFailures;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Spatie\Multitenancy\Jobs\TenantAware;

class SendWelcomeNotification implements ShouldBeUnique, ShouldQueue, TenantAware
{
    use Dispatchable, InteractsWithQueue, LogsJobFailures, Queueable, SerializesModels;

    public int $tries = 3;

    public int $uniqueFor = 86400;

    public function __construct(public readonly int $memberId) {}

    public function uniqueId(): string
    {
        return 'welcome:member:'.$this->memberId;
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

        if (! (bool) data_get($settings, 'notifications.whatsapp.send_welcome_message', true)) {
            return;
        }

        if (! (bool) ($member->whatsapp_enabled ?? true)) {
            return;
        }

        $latestSubscription = $member->subscriptions()->latest('end_date')->with('plan')->first();
        $gymName = (string) data_get($settings, 'general.gym_name', 'Gym');
        $planName = (string) ($latestSubscription?->plan?->name ?? 'Membership');
        $expiry = $latestSubscription?->end_date?->format('d-m-Y') ?? '';

        $waOk = $msg91->sendWhatsApp($member, 'welcome', [
            (string) $member->name,
            $gymName,
            $planName,
            $expiry,
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

        $sms = "Welcome to {$gymName}! Your {$planName} membership is active till {$expiry}.";
        $smsOk = $msg91->sendSms($member, $sms);

        if (! $smsOk) {
            $this->release(3600);
        }
    }
}
