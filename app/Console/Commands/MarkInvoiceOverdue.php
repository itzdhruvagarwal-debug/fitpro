<?php

namespace App\Console\Commands;

use App\Models\Gym;
use App\Models\Invoice;
use App\Support\AppConfig;
use App\Support\Tenancy\TenantContext;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class MarkInvoiceOverdue extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gymie:invoices {--mark-overdue : Mark invoices as overdue based on due date}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Perform operations on invoices (e.g., mark as overdue)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (! $this->option('mark-overdue')) {
            $this->info('No operation selected.');

            return self::SUCCESS;
        }

        $totalUpdated = 0;
        /** @var array<string, int> $summary */
        $summary = [];

        foreach (Gym::query()->cursor() as $gym) {
            if ($this->shouldSkipTenant($gym)) {
                continue;
            }

            $updatedCount = TenantContext::run($gym, function (): int {
                $today = Carbon::today(AppConfig::timezone());

                return Invoice::query()
                    ->whereIn('status', ['issued', 'partial'])
                    ->whereNotNull('due_date')
                    ->whereDate('due_date', '<', $today)
                    ->where('due_amount', '>', 0)
                    ->update(['status' => 'overdue']);
            });

            if ($updatedCount > 0) {
                $summary[$gym->name] = $updatedCount;
                $totalUpdated += $updatedCount;
            }
        }

        if ($totalUpdated === 0) {
            $this->info('No invoices needed overdue updates.');

            return self::SUCCESS;
        }

        foreach ($summary as $gymName => $updatedCount) {
            $this->info("{$gymName}: {$updatedCount} invoice(s) marked as overdue.");
        }

        return self::SUCCESS;
    }

    private function shouldSkipTenant(Gym $gym): bool
    {
        return $gym->status === 'suspended'
            || (! $gym->isOnTrial() && ! $gym->isActive());
    }
}
