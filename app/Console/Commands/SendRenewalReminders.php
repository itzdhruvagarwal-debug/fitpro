<?php

namespace App\Console\Commands;

use App\Helpers\Helpers;
use App\Jobs\SendExpiryWarningNotification;
use App\Jobs\SendRenewalReminderNotification;
use App\Models\Gym;
use App\Models\Subscription;
use App\Support\AppConfig;
use App\Support\Tenancy\TenantContext;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendRenewalReminders extends Command
{
    protected $signature = 'notifications:send-renewal-reminders';

    protected $description = 'Send WhatsApp/SMS renewal reminders and expiry warnings';

    public function handle(): int
    {
        $settings = Helpers::getSettings();
        $days = data_get($settings, 'notifications.whatsapp.renewal_reminder_days', [7, 3, 1]);

        if (! is_array($days)) {
            $days = [7, 3, 1];
        }

        $normalizedDays = array_values(array_unique(array_filter(array_map(static function (mixed $d): ?int {
            if (! is_numeric($d)) {
                return null;
            }

            $d = (int) $d;

            return $d > 0 ? $d : null;
        }, $days))));

        if (empty($normalizedDays)) {
            $normalizedDays = [7, 3, 1];
        }

        sort($normalizedDays);

        $timezone = AppConfig::timezone();
        $today = Carbon::today($timezone);
        $summary = [
            'renewal_reminders' => 0,
            'expiry_warnings' => 0,
        ];

        foreach (Gym::query()->get() as $gym) {
            if ($this->shouldSkipTenant($gym)) {
                continue;
            }

            $tenantSummary = TenantContext::run(
                $gym,
                fn (): array => $this->dispatchTenantReminders($normalizedDays, $today),
            );

            $summary['renewal_reminders'] += $tenantSummary['renewal_reminders'];
            $summary['expiry_warnings'] += $tenantSummary['expiry_warnings'];
        }

        Log::info('Renewal reminders dispatched.', [
            'days' => $normalizedDays,
            'summary' => $summary,
        ]);

        $this->info('Dispatched: '.$summary['renewal_reminders'].' renewal reminder(s), '.$summary['expiry_warnings'].' expiry warning(s).');

        return self::SUCCESS;
    }

    /**
     * @param  list<int>  $normalizedDays
     * @return array{renewal_reminders: int, expiry_warnings: int}
     */
    private function dispatchTenantReminders(array $normalizedDays, Carbon $today): array
    {
        $summary = [
            'renewal_reminders' => 0,
            'expiry_warnings' => 0,
        ];

        foreach ($normalizedDays as $x) {
            $targetDate = $today->copy()->addDays($x)->toDateString();

            $subs = Subscription::query()
                ->with(['member'])
                ->whereDate('end_date', '=', $targetDate)
                ->whereNull('deleted_at')
                ->get();

            foreach ($subs as $sub) {
                if (! $sub->member) {
                    continue;
                }

                SendRenewalReminderNotification::dispatch(
                    memberId: (int) $sub->member->getKey(),
                    daysLeft: (int) $x,
                    forDate: $targetDate,
                );

                $summary['renewal_reminders']++;
            }
        }

        $expiryDate = $today->toDateString();
        $expiringToday = Subscription::query()
            ->with(['member'])
            ->whereDate('end_date', '=', $expiryDate)
            ->whereNull('deleted_at')
            ->get();

        foreach ($expiringToday as $sub) {
            if (! $sub->member) {
                continue;
            }

            SendExpiryWarningNotification::dispatch(
                memberId: (int) $sub->member->getKey(),
                forDate: $expiryDate,
            );

            $summary['expiry_warnings']++;
        }

        return $summary;
    }

    private function shouldSkipTenant(Gym $gym): bool
    {
        return $gym->status === 'suspended'
            || (! $gym->isOnTrial() && ! $gym->isActive());
    }
}
