<?php

namespace App\Jobs;

use App\Jobs\Concerns\LogsJobFailures;
use App\Models\Member;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Spatie\Multitenancy\Jobs\TenantAware;

class ProcessRazorpaySubscriptionEvent implements ShouldQueue, TenantAware
{
    use Dispatchable, InteractsWithQueue, LogsJobFailures, Queueable, SerializesModels;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public readonly array $payload,
        public readonly bool $active,
        public readonly string $status,
    ) {}

    public function handle(): void
    {
        $subscriptionId = (string) data_get($this->payload, 'payload.subscription.entity.id', '');
        if ($subscriptionId === '') {
            return;
        }

        $member = Member::query()
            ->where('razorpay_subscription_id', $subscriptionId)
            ->first();

        if (! $member) {
            return;
        }

        $member->update([
            'upi_autopay_active' => $this->active,
            'upi_mandate_status' => $this->status,
        ]);
    }
}
