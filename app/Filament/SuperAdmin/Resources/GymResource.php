<?php

namespace App\Filament\SuperAdmin\Resources;

use App\Filament\SuperAdmin\Resources\GymResource\Pages\CreateGym;
use App\Filament\SuperAdmin\Resources\GymResource\Pages\EditGym;
use App\Filament\SuperAdmin\Resources\GymResource\Pages\ListGyms;
use App\Models\Gym;
use App\Models\Member;
use App\Models\PaymentTransaction;
use BackedEnum;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class GymResource extends Resource
{
    protected static ?string $model = Gym::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-building-office-2';

    public static function getModelLabel(): string
    {
        return 'Gym';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Gyms';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')->required()->maxLength(255),
                TextInput::make('slug')->required()->maxLength(255),
                TextInput::make('owner_name')->maxLength(255),
                TextInput::make('owner_email')->email()->maxLength(255),
                TextInput::make('owner_phone')->maxLength(30),
                Select::make('plan')->options([
                    'starter' => 'Starter',
                    'growth' => 'Growth',
                    'pro' => 'Pro',
                    'trial' => 'Trial',
                ])->default('trial'),
                DateTimePicker::make('plan_expires_at')->seconds(false),
                DateTimePicker::make('trial_ends_at')->seconds(false),
                Select::make('status')->options([
                    'active' => 'Active',
                    'trial' => 'Trial',
                    'suspended' => 'Suspended',
                ])->default('trial'),
                Toggle::make('settings.notifications_enabled')
                    ->label('Notifications Enabled')
                    ->default(true),
            ])
            ->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('slug')->label('Subdomain')->searchable()
                    ->formatStateUsing(fn (string $state): string => $state.'.'.config('app.base_domain')),
                TextColumn::make('owner_name')->label('Owner')->searchable()->placeholder('-'),
                TextColumn::make('owner_phone')->label('Phone')->searchable()->placeholder('-'),
                TextColumn::make('plan')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'starter' => 'info',
                        'growth' => 'warning',
                        'pro' => 'primary',
                        default => 'gray',
                    }),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'trial' => 'warning',
                        'suspended' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('member_count')
                    ->label('Members')
                    ->state(fn (Gym $record): int => Member::query()->withoutGlobalScopes()->where('gym_id', $record->id)->count()),
                TextColumn::make('revenue_this_month')
                    ->label('Revenue (This Month)')
                    ->state(function (Gym $record): string {
                        $sum = PaymentTransaction::query()
                            ->withoutGlobalScopes()
                            ->where('gym_id', $record->id)
                            ->where('status', 'captured')
                            ->whereYear('created_at', now()->year)
                            ->whereMonth('created_at', now()->month)
                            ->sum('amount');

                        return 'INR '.number_format((float) $sum, 2);
                    }),
                TextColumn::make('plan_expires_at')->dateTime()->sortable()->placeholder('-'),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->recordActions([
                Action::make('view_dashboard')
                    ->label('View Dashboard')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (Gym $record): string => 'https://'.$record->slug.'.'.config('app.base_domain').'/admin', shouldOpenInNewTab: true),
                Action::make('suspend')
                    ->label('Suspend')
                    ->icon('heroicon-o-no-symbol')
                    ->requiresConfirmation()
                    ->visible(fn (Gym $record): bool => $record->status !== 'suspended')
                    ->action(fn (Gym $record) => $record->update(['status' => 'suspended'])),
                Action::make('activate')
                    ->label('Activate')
                    ->icon('heroicon-o-check-circle')
                    ->requiresConfirmation()
                    ->visible(fn (Gym $record): bool => $record->status === 'suspended')
                    ->action(fn (Gym $record) => $record->update(['status' => 'active'])),
                Action::make('extend_plan')
                    ->label('Extend Plan')
                    ->icon('heroicon-o-calendar-days')
                    ->form([
                        DateTimePicker::make('plan_expires_at')->label('Plan Expires At')->seconds(false)->required(),
                    ])
                    ->action(fn (Gym $record, array $data) => $record->update(['plan_expires_at' => $data['plan_expires_at']])),
            ])
            ->defaultSort('id', 'desc');
    }

    /**
     * Superadmin resources should never be tenant scoped.
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withoutGlobalScopes();
    }

    public static function getPages(): array
    {
        return [
            'index' => ListGyms::route('/'),
            'create' => CreateGym::route('/create'),
            'edit' => EditGym::route('/{record}/edit'),
        ];
    }
}
