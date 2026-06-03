<?php

namespace App\Filament\Widgets\Invoices;

use App\Helpers\Helpers;
use App\Models\Invoice;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;

/**
 * Monthly GST and revenue snapshot for the invoices list page.
 */
class InvoiceGstStatsWidget extends StatsOverviewWidget
{
    protected static bool $isDiscovered = false;

    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    /**
     * @var int | array<string, ?int> | null
     */
    protected int|array|null $columns = 4;

    /**
     * @return Builder<Invoice>
     */
    private function monthQuery(): Builder
    {
        $now = now();

        return Invoice::query()
            ->where(function (Builder $query) use ($now): void {
                $query
                    ->where(function (Builder $inner) use ($now): void {
                        $inner
                            ->whereYear('invoice_date', $now->year)
                            ->whereMonth('invoice_date', $now->month);
                    })
                    ->orWhere(function (Builder $inner) use ($now): void {
                        $inner
                            ->whereNull('invoice_date')
                            ->whereYear('date', $now->year)
                            ->whereMonth('date', $now->month);
                    });
            })
            ->whereNotIn('status', ['cancelled']);
    }

    /**
     * @return array<int, Stat>
     */
    protected function getStats(): array
    {
        $monthLabel = now()->format('F Y');

        $invoiceCount = (int) $this->monthQuery()->count();
        $totalRevenue = (float) $this->monthQuery()->sum('total_amount');
        $totalGst = (float) $this->monthQuery()->sum('total_tax');

        $pendingCount = Invoice::query()
            ->whereIn('status', ['issued', 'partial', 'overdue'])
            ->count();

        return [
            Stat::make('Invoices This Month', (string) $invoiceCount)
                ->icon('heroicon-o-document-text')
                ->description($monthLabel)
                ->descriptionColor('gray'),
            Stat::make('Revenue This Month', Helpers::formatCurrency($totalRevenue))
                ->icon('heroicon-o-banknotes')
                ->description('Total billed amount')
                ->descriptionColor('gray'),
            Stat::make('GST Collected This Month', Helpers::formatCurrency($totalGst))
                ->icon('heroicon-o-receipt-percent')
                ->description('CGST + SGST + IGST')
                ->descriptionColor('gray'),
            Stat::make('Pending Invoices', (string) $pendingCount)
                ->icon('heroicon-o-clock')
                ->description('Issued, partial, or overdue')
                ->descriptionColor('warning'),
        ];
    }
}
