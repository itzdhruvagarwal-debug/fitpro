<?php

namespace App\Console\Commands;

use App\Helpers\Helpers;
use App\Models\Gym;
use App\Models\Subscription;
use App\Models\User;
use App\Support\AppConfig;
use App\Support\Tenancy\TenantContext;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Illuminate\Console\Command;

class MarkSubscriptionsStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gymie:subscriptions
                            {--mark-expired : Mark expired subscriptions}
                            {--mark-expiring : Mark subscriptions expiring within the configured window}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mark subscriptions as expiring or expired';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $timezone = AppConfig::timezone();
        $today = Carbon::today($timezone);
        $expiringDays = Helpers::getSubscriptionExpiringDays();
        $expiringThreshold = $today->copy()->addDays($expiringDays);

        $runExpiredOnly = (bool) $this->option('mark-expired');
        $runExpiringOnly = (bool) $this->option('mark-expiring');
        $runAll = ! $runExpiredOnly && ! $runExpiringOnly;
        $roleName = (string) config('filament-shield.super_admin.name', 'super_admin');

        /** @var array<string, list<string>> $summary */
        $summary = [];

        foreach (Gym::query()->cursor() as $gym) {
            if ($this->shouldSkipTenant($gym)) {
                continue;
            }

            $tenantSummary = TenantContext::run(
                $gym,
                fn (): array => $this->updateTenantSubscriptions(
                    today: $today,
                    expiringThreshold: $expiringThreshold,
                    expiringDays: $expiringDays,
                    runAll: $runAll,
                    runExpiredOnly: $runExpiredOnly,
                    runExpiringOnly: $runExpiringOnly,
                ),
            );

            if (empty($tenantSummary)) {
                continue;
            }

            $summary[$gym->name] = $tenantSummary;

            TenantContext::run($gym, function () use ($tenantSummary, $roleName): void {
                User::role($roleName)->get()->each(function (User $admin) use ($tenantSummary): void {
                    Notification::make()
                        ->title(__('app.notifications.subscription_status_update_title'))
                        ->body(__('app.notifications.subscription_status_update_body', ['summary' => implode(', ', $tenantSummary)]))
                        ->info()
                        ->sendToDatabase($admin);
                });
            });
        }

        if (empty($summary)) {
            $this->info('No subscription statuses needed updating.');

            return self::SUCCESS;
        }

        foreach ($summary as $gymName => $tenantSummary) {
            $this->info($gymName.': '.implode(', ', $tenantSummary));
        }

        return self::SUCCESS;
    }

    /**
     * @return list<string>
     */
    private function updateTenantSubscriptions(
        Carbon $today,
        Carbon $expiringThreshold,
        int $expiringDays,
        bool $runAll,
        bool $runExpiredOnly,
        bool $runExpiringOnly,
    ): array {
        $summary = [];

        if ($runAll || $runExpiredOnly) {
            $expiredCount = Subscription::query()
                ->whereDate('end_date', '<', $today)
                ->whereNotIn('status', ['expired', 'renewed'])
                ->whereDoesntHave('renewals')
                ->update(['status' => 'expired']);

            if ($expiredCount > 0) {
                $summary[] = "{$expiredCount} expired";
            }
        }

        if ($runAll || $runExpiredOnly) {
            $renewedCount = Subscription::query()
                ->whereDate('end_date', '<', $today)
                ->where('status', '!=', 'renewed')
                ->whereHas('renewals')
                ->update(['status' => 'renewed']);

            if ($renewedCount > 0) {
                $summary[] = "{$renewedCount} renewed";
            }
        }

        if ($runAll) {
            $upcomingCount = Subscription::query()
                ->whereDate('start_date', '>', $today)
                ->where('status', '!=', 'renewed')
                ->where('status', '!=', 'upcoming')
                ->update(['status' => 'upcoming']);

            if ($upcomingCount > 0) {
                $summary[] = "{$upcomingCount} upcoming";
            }
        }

        if ($runAll || $runExpiringOnly) {
            $expiringCount = Subscription::query()
                ->whereDate('start_date', '<=', $today)
                ->whereBetween('end_date', [$today->toDateString(), $expiringThreshold->toDateString()])
                ->where('status', '!=', 'renewed')
                ->where('status', '!=', 'expiring')
                ->where('status', '!=', 'expired')
                ->update(['status' => 'expiring']);

            if ($expiringCount > 0) {
                $summary[] = "{$expiringCount} expiring (<= {$expiringDays} days)";
            }
        }

        if ($runAll) {
            $ongoingCount = Subscription::query()
                ->whereDate('start_date', '<=', $today)
                ->whereDate('end_date', '>', $expiringThreshold)
                ->whereNotIn('status', ['ongoing', 'expired', 'renewed'])
                ->update(['status' => 'ongoing']);

            if ($ongoingCount > 0) {
                $summary[] = "{$ongoingCount} ongoing";
            }
        }

        return $summary;
    }

    private function shouldSkipTenant(Gym $gym): bool
    {
        return $gym->status === 'suspended'
            || (! $gym->isOnTrial() && ! $gym->isActive());
    }
}
