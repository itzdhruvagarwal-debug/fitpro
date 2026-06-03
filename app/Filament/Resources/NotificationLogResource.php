<?php

namespace App\Filament\Resources;

use App\Filament\Resources\NotificationLogResource\Pages\ListNotificationLogs;
use App\Filament\Resources\NotificationLogResource\Pages\ViewNotificationLog;
use App\Jobs\RetryNotificationLog;
use App\Models\NotificationLog;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class NotificationLogResource extends Resource
{
    protected static ?string $model = NotificationLog::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    public static function getModelLabel(): string
    {
        return 'Notification Log';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Notification Logs';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('member.name')
                    ->label('Member')
                    ->searchable()
                    ->placeholder('-'),
                TextColumn::make('channel')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'whatsapp' => 'WA',
                        'sms' => 'SMS',
                        'email' => 'Email',
                        default => strtoupper($state),
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'whatsapp' => 'success',
                        'sms' => 'info',
                        'email' => 'gray',
                        default => 'gray',
                    }),
                TextColumn::make('template_name')
                    ->label('Template')
                    ->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'sent' => 'success',
                        'failed' => 'danger',
                        'pending' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('phone')
                    ->label('Phone')
                    ->formatStateUsing(function (?string $state): string {
                        if (! filled($state)) {
                            return '-';
                        }
                        $digits = preg_replace('/\D/', '', $state) ?: '';
                        if (strlen($digits) < 6) {
                            return $state;
                        }
                        $last4 = substr($digits, -4);

                        return '91XXXXX'.$last4;
                    })
                    ->toggleable(),
                TextColumn::make('sent_at')
                    ->label('Sent')
                    ->since()
                    ->sortable()
                    ->placeholder('-'),
            ])
            ->filters([
                SelectFilter::make('channel')
                    ->options([
                        'whatsapp' => 'WhatsApp',
                        'sms' => 'SMS',
                        'email' => 'Email',
                    ]),
                SelectFilter::make('status')
                    ->options([
                        'sent' => 'Sent',
                        'failed' => 'Failed',
                        'pending' => 'Pending',
                    ]),
                Filter::make('sent_at')
                    ->form([
                        DatePicker::make('from')->label('From'),
                        DatePicker::make('until')->label('Until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'] ?? null, fn (Builder $q, $date): Builder => $q->whereDate('sent_at', '>=', $date))
                            ->when($data['until'] ?? null, fn (Builder $q, $date): Builder => $q->whereDate('sent_at', '<=', $date));
                    }),
            ])
            ->recordActions([
                ViewAction::make(),
                Action::make('view_response')
                    ->label('Response JSON')
                    ->icon('heroicon-o-code-bracket')
                    ->modalHeading('MSG91 Response')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close')
                    ->form([
                        Placeholder::make('json')
                            ->content(function (NotificationLog $record): string {
                                $json = $record->msg91_response ?? null;
                                if (! $json) {
                                    return '-';
                                }

                                return json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?: '-';
                            }),
                    ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('retry_failed')
                        ->label('Retry Failed')
                        ->icon('heroicon-o-arrow-path')
                        ->requiresConfirmation()
                        ->action(function ($records): void {
                            foreach ($records as $record) {
                                if (! $record instanceof NotificationLog) {
                                    continue;
                                }
                                if ($record->status !== 'failed') {
                                    continue;
                                }
                                RetryNotificationLog::dispatch(notificationLogId: (int) $record->getKey());
                            }
                        }),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListNotificationLogs::route('/'),
            'view' => ViewNotificationLog::route('/{record}'),
        ];
    }
}
