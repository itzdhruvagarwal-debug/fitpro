<?php

namespace App\Filament\Resources\NotificationLogResource\Widgets;

use App\Models\NotificationLog;
use App\Support\AppConfig;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class NotificationStatsWidget extends StatsOverviewWidget
{
    protected int|string|array $columnSpan = 'full';

    /**
     * @return array<int, Stat>
     */
    protected function getStats(): array
    {
        $today = now(AppConfig::timezone())->toDateString();

        $sentToday = NotificationLog::query()
            ->where('status', 'sent')
            ->whereDate('sent_at', $today)
            ->count();

        $waTodayTotal = NotificationLog::query()
            ->where('channel', 'whatsapp')
            ->whereDate('created_at', $today)
            ->count();

        $waTodaySent = NotificationLog::query()
            ->where('channel', 'whatsapp')
            ->where('status', 'sent')
            ->whereDate('created_at', $today)
            ->count();

        $waSuccessRate = $waTodayTotal > 0
            ? round(($waTodaySent / $waTodayTotal) * 100, 1)
            : null;

        $failed = NotificationLog::query()
            ->where('status', 'failed')
            ->whereDate('created_at', $today)
            ->count();

        return [
            Stat::make('Messages Sent Today', (string) $sentToday)
                ->icon('heroicon-o-paper-airplane'),
            Stat::make('WhatsApp Success Rate', $waSuccessRate === null ? '-' : ($waSuccessRate.'%'))
                ->icon('heroicon-o-chart-bar'),
            Stat::make('Failed Messages Today', (string) $failed)
                ->icon('heroicon-o-exclamation-triangle'),
        ];
    }
}
