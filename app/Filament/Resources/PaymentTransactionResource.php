<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentTransactionResource\Pages\ListPaymentTransactions;
use App\Models\PaymentTransaction;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PaymentTransactionResource extends Resource
{
    protected static ?string $model = PaymentTransaction::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-credit-card';

    public static function getModelLabel(): string
    {
        return 'Payment Transaction';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Payment Transactions';
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
                    ->label('Member Name')
                    ->searchable(),
                TextColumn::make('amount')
                    ->numeric(decimalPlaces: 2)
                    ->prefix('INR ')
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'captured' => 'success',
                        'failed' => 'danger',
                        'created' => 'warning',
                        'refunded' => 'info',
                        default => 'gray',
                    }),
                TextColumn::make('payment_method')
                    ->label('Payment Method')
                    ->placeholder('-'),
                TextColumn::make('paid_at')
                    ->label('Paid At')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('-'),
                TextColumn::make('razorpay_payment_id')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'created' => 'Created',
                        'captured' => 'Captured',
                        'failed' => 'Failed',
                        'refunded' => 'Refunded',
                    ]),
                SelectFilter::make('payment_method')
                    ->options([
                        'upi' => 'UPI',
                        'card' => 'Card',
                        'netbanking' => 'Netbanking',
                    ]),
                Filter::make('paid_at')
                    ->form([
                        DatePicker::make('from'),
                        DatePicker::make('until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'] ?? null, fn (Builder $query, $date): Builder => $query->whereDate('paid_at', '>=', $date))
                            ->when($data['until'] ?? null, fn (Builder $query, $date): Builder => $query->whereDate('paid_at', '<=', $date));
                    }),
            ])
            ->recordActions([])
            ->toolbarActions([]);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return [
            'member.name',
            'razorpay_payment_id',
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPaymentTransactions::route('/'),
        ];
    }
}
