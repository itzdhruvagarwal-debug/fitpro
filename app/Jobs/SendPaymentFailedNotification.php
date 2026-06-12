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

class SendPaymentFailedNotification implements ShouldBeUnique, ShouldQueue, TenantAware
{
    use Dispatchable, InteractsWithQueue, LogsJobFailures, Queueable, SerializesModels;

    public int $tries = 3;

    public int $uniqueFor = 86400;

    public function __construct(
        public readonly int $memberId,
        public readonly string $reason,
        public readonly ?string $amount = null,
    ) {}

    public function uniqueId(): string
    {
        return 'payment_failed:member:'.$this->memberId.':'.md5($this->reason.'|'.$this->amount);
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
        if (! (bool) data_get($settings, 'notifications.whatsapp.send_payment_failed', true)) {
            return;
        }
        if (! (bool) ($member->whatsapp_enabled ?? true)) {
            return;
        }

        // NOTE: No public retry link exists yet; keep blank for now.
        $retryLink = '';
        $amount = (string) ($this->amount ?? '');

        $waOk = $msg91->sendWhatsApp($member, 'payment_failed', [
            (string) $member->name,
            $amount,
            (string) $this->reason,
            $retryLink,
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

        $sms = "Payment failed for your gym membership. Please retry: {$retryLink}";
        $smsOk = $msg91->sendSms($member, $sms);

        if (! $smsOk) {
            $this->release(3600);
        }
    }
}
