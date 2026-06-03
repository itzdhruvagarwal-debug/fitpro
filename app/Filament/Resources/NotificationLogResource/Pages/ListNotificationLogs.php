<?php

namespace App\Filament\Resources\NotificationLogResource\Pages;

use App\Filament\Resources\NotificationLogResource;
use App\Filament\Resources\NotificationLogResource\Widgets\NotificationStatsWidget;
use Filament\Resources\Pages\ListRecords;

class ListNotificationLogs extends ListRecords
{
    protected static string $resource = NotificationLogResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            NotificationStatsWidget::class,
        ];
    }
}
