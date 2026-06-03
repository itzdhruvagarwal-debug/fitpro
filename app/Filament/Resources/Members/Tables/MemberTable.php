<?php

namespace App\Filament\Resources\Members\Tables;

use App\Models\Member;
use App\Models\Plan;
use App\Services\RazorpayService;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Throwable;

class MemberTable
{
    /**
     * Configure the member table schema.
     */
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                ImageColumn::make('photo')
                    ->circular()
                    ->defaultImageUrl(fn (Member $record): string => 'https://ui-avatars.com/api/?background=000&color=fff&name='.$record->name),
                TextColumn::make('code')
                    ->searchable(),
                TextColumn::make('name')
                    ->searchable()
                    ->label(__('app.fields.name')),
                TextColumn::make('email')
                    ->searchable()
                    ->label(__('app.fields.email')),
                TextColumn::make('gstin')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('GSTIN'),
                TextColumn::make('gender')
                    ->searchable()
                    ->label(__('app.fields.gender')),
                TextColumn::make('contact')
                    ->searchable()
                    ->label(__('app.fields.contact')),
                TextColumn::make('emergency_contact')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label(__('app.fields.emergency_contact')),
                TextColumn::make('created_at')
                    ->sortable()
                    ->date('d-m-Y')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label(__('app.fields.date')),
                TextColumn::make('status')
                    ->badge()
                    ->label(__('app.fields.status')),
            ])
            ->emptyStateIcon('heroicon-o-user-group')
            ->emptyStateHeading(function ($livewire): string {
                $dates = $livewire->getTableFilterState('date') ?? [];
                [$from, $to] = [$dates['date_from'] ?? null, $dates['date_to'] ?? null];
                $records = (string) __('app.resources.members.plural');
                $tab = (string) ($livewire->activeTab ?? 'all');
                $status = $tab !== 'all' ? (string) __('app.status.'.$tab) : null;

                if (! $from && ! $to) {
                    return $status
                        ? __('app.empty.no_status_records', ['status' => $status, 'records' => $records])
                        : __('app.empty.no_records', ['records' => $records]);
                }

                if ($tab === 'all') {
                    return __('app.empty.no_records_in_range', ['records' => $records]);
                }

                $base = __('app.empty.no_status_records', ['status' => $status, 'records' => $records]);

                return Member::where('status', $tab)->exists()
                    ? __('app.empty.no_status_records_in_range', ['status' => $status, 'records' => $records])
                    : $base;
            })
            ->emptyStateDescription(function ($livewire): string {
                $dates = $livewire->getTableFilterState('date') ?? [];
                [$fromRaw, $toRaw] = [$dates['date_from'] ?? null, $dates['date_to'] ?? null];
                $records = (string) __('app.resources.members.plural');
                $record = (string) __('app.resources.members.singular');
                $tab = (string) ($livewire->activeTab ?? 'all');
                $status = $tab !== 'all' ? (string) __('app.status.'.$tab) : null;

                if (! $fromRaw && ! $toRaw) {
                    return $status
                        ? __('app.empty.no_records_marked_as', ['records' => $records, 'status' => $status])
                        : __('app.empty.create_to_get_started', ['resource' => $record]);
                }

                $from = $fromRaw ? Carbon::parse($fromRaw)->format('d-m-Y') : (string) __('app.common.the_beginning');
                $to = $toRaw ? Carbon::parse($toRaw)->format('d-m-Y') : (string) __('app.common.today');

                if ($tab === 'all') {
                    return __('app.empty.found_none_between', ['records' => $records, 'from' => $from, 'to' => $to]);
                }

                if (! Member::where('status', $tab)->exists()) {
                    return __('app.empty.no_records_marked_as', ['records' => $records, 'status' => $status]);
                }

                return __('app.empty.found_none_status_between', ['status' => $status, 'records' => $records, 'from' => $from, 'to' => $to]);
            })
            ->emptyStateActions([
                CreateAction::make()
                    ->icon('heroicon-o-plus')
                    ->label(__('app.actions.new', ['resource' => __('app.resources.members.singular')]))
                    ->hidden(fn (): bool => Member::exists()),
            ])
            ->filters([
                TrashedFilter::make(),
                Filter::make('date')
                    ->schema([
                        DatePicker::make('date_from')
                            ->label(__('app.fields.date_from'))
                            ->native(false)
                            ->suffixIcon('heroicon-m-calendar-days'),
                        DatePicker::make('date_to')
                            ->label(__('app.fields.date_to'))
                            ->native(false)
                            ->suffixIcon('heroicon-m-calendar-days'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['date_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['date_to'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->recordActions([
                ActionGroup::make([
                    ActionGroup::make([
                        Action::make('heading_actions')
                            ->label(__('app.fields.status'))
                            ->disabled()
                            ->color('gray'),
                        Action::make('mark_as_active')
                            ->color('success')
                            ->label(__('app.actions.mark_as_active'))
                            ->requiresConfirmation()
                            ->action(fn (Member $record) => tap($record, function ($record) {
                                $record->update(['status' => 'active']);
                                Notification::make()
                                    ->title(__('app.notifications.member_activated'))
                                    ->success()
                                    ->send();
                            }))
                            ->visible(fn ($record) => $record->status->value === 'inactive'),
                        Action::make('mark_as_inactive')
                            ->color('danger')
                            ->label(__('app.actions.mark_as_inactive'))
                            ->requiresConfirmation()
                            ->action(fn (Member $record) => tap($record, function ($record) {
                                $record->update(['status' => 'inactive']);
                                Notification::make()
                                    ->title(__('app.notifications.member_deactivated'))
                                    ->danger()
                                    ->send();
                            }))
                            ->visible(fn ($record) => $record->status->value === 'active'),
                    ])->dropdown(false),
                    ActionGroup::make([
                        Action::make('heading_actions')
                            ->label(__('app.actions.record_actions'))
                            ->disabled()
                            ->color('gray'),
                        ViewAction::make(),
                        EditAction::make()->hiddenLabel(),
                        DeleteAction::make()->hiddenLabel(),
                    ])->dropdown(false),
                    Action::make('collect_payment')
                        ->label('Collect Payment')
                        ->icon('heroicon-o-credit-card')
                        ->slideOver()
                        ->form([
                            TextInput::make('amount')
                                ->numeric()
                                ->required()
                                ->minValue(1)
                                ->default(function (Member $record): float {
                                    return (float) ($record->subscriptions()->latest('end_date')->with('plan')->first()?->plan?->amount ?? 0);
                                }),
                            Toggle::make('setup_autopay')
                                ->label('Setup UPI AutoPay')
                                ->default(false),
                            Select::make('plan_id')
                                ->label('Plan')
                                ->options(fn (): array => Plan::query()->pluck('name', 'id')->all())
                                ->visible(fn (callable $get): bool => (bool) $get('setup_autopay'))
                                ->required(fn (callable $get): bool => (bool) $get('setup_autopay')),
                        ])
                        ->action(function (array $data, Member $record, $livewire): void {
                            /** @var RazorpayService $razorpay */
                            $razorpay = app(RazorpayService::class);

                            try {
                                if ((bool) ($data['setup_autopay'] ?? false)) {
                                    $plan = Plan::query()->findOrFail((int) $data['plan_id']);
                                    $checkout = $razorpay->createSubscription($record, $plan) + [
                                        'member_name' => $record->name,
                                        'member_email' => $record->email,
                                        'member_phone' => $record->contact,
                                        'description' => 'UPI AutoPay mandate setup',
                                        'amount' => (int) round(((float) $plan->amount) * 100),
                                    ];
                                } else {
                                    $checkout = $razorpay->createOrder(
                                        $record,
                                        (float) $data['amount'],
                                        'Member payment',
                                    ) + [
                                        'member_name' => $record->name,
                                        'member_email' => $record->email,
                                        'member_phone' => $record->contact,
                                        'description' => 'Membership Payment',
                                    ];
                                }

                                $livewire->dispatch('open-razorpay-checkout', config: $checkout);
                            } catch (Throwable) {
                                Notification::make()
                                    ->title('Unable to start Razorpay checkout.')
                                    ->danger()
                                    ->send();
                            }
                        }),
                ]),
            ])->recordUrl(fn ($record): string => route('filament.admin.resources.members.view', $record->id))
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
