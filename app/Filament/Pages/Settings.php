<?php

namespace App\Filament\Pages;

use App\Contracts\SettingsRepository;
use App\Helpers\Helpers;
use App\Models\Member;
use App\Services\Msg91Service;
use Carbon\Carbon;
use Filament\Actions\Action as FormAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

/**
 * @property-read Schema $form
 */
class Settings extends Page implements HasForms
{
    use InteractsWithForms;

    /** @var string|null Page title */
    protected static ?string $title = null;

    /** @var string View file for the settings page */
    protected string $view = 'filament.pages.settings';

    /** @var array<string, mixed>|null Stores the settings data */
    public ?array $data = [];

    /** @var string|null Stores the uploaded settings file */
    public ?string $settings_file = null;

    /**
     * Mount the page and load settings from the storage.
     */
    public function mount(): void
    {
        $settings = Helpers::getSettings();
        $this->data = $settings;
        $general = is_array($this->data['general'] ?? null) ? $this->data['general'] : [];

        // Ensure gym_logo is always set correctly
        foreach (['gym_logo'] as $logoType) {
            if (! empty($general[$logoType]) && is_array($general[$logoType])) {
                $general[$logoType] = $general[$logoType];
            }
        }

        $this->data['general'] = $general;

        $this->form->fill($settings);
    }

    public function getTitle(): string
    {
        return __('app.settings.title');
    }

    public static function getNavigationLabel(): string
    {
        return __('app.settings.title');
    }

    /**
     * Defines the form schema with multiple tabs.
     *
     * @return array<int, Component>
     */
    protected function getFormSchema(): array
    {
        return [
            Tabs::make(__('app.settings.title'))
                ->tabs([
                    $this->generalTab(),
                    $this->invoiceTab(),
                    $this->memberTab(),
                    $this->notificationTab(),
                    $this->chargesTab(),
                    $this->expensesTab(),
                    $this->subscriptionsTab(),
                ]),
        ];
    }

    /**
     * General Tab Schema.
     */
    private function generalTab(): Tab
    {
        return Tab::make(__('app.settings.tabs.gym_info'))
            ->icon('heroicon-m-briefcase')
            ->schema([
                Section::make(__('app.settings.sections.general_information'))
                    ->aside()
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('general.gym_name')
                                    ->label(__('app.settings.fields.gym_name')),
                                Select::make('general.currency')
                                    ->label(__('app.settings.fields.currency'))
                                    ->options(Helpers::getCurrencies())
                                    ->searchable(),
                                FileUpload::make('general.gym_logo')
                                    ->label(__('app.settings.fields.gym_logo'))
                                    ->disk('public')
                                    ->directory('images')
                                    ->preserveFilenames()
                                    ->imageEditor()
                                    ->deletable()
                                    ->visibility('public')
                                    ->image()
                                    ->afterStateUpdated(fn ($state, callable $set) => $this->handleFileUpload($state, 'gym_logo', $set))
                                    ->columnSpanFull(),
                                DatePicker::make('general.financial_year_start')
                                    ->native(false)
                                    ->label(__('app.settings.fields.financial_year_start'))
                                    ->suffixIcon('heroicon-o-calendar-days')
                                    ->displayFormat('d/m/Y'),
                                DatePicker::make('general.financial_year_end')
                                    ->native(false)
                                    ->label(__('app.settings.fields.financial_year_end'))
                                    ->suffixIcon('heroicon-o-calendar-days')
                                    ->displayFormat('d/m/Y'),
                            ]),
                    ])
                    ->columnSpan(3),

                Section::make(__('app.settings.sections.address'))
                    ->aside()
                    ->schema([
                        Grid::make(1)
                            ->schema([
                                Textarea::make('general.address')
                                    ->label(__('app.settings.fields.address')),
                            ]),
                        Grid::make(4)
                            ->schema([
                                Select::make('general.country')
                                    ->label(__('app.settings.fields.country'))
                                    ->options(Helpers::getCountries())
                                    ->searchable()
                                    ->reactive()
                                    ->afterStateUpdated(fn ($state, callable $set) => [
                                        $set('general.state', null),
                                        $set('general.city', null),
                                    ]),
                                Select::make('general.state')
                                    ->label(__('app.settings.fields.state'))
                                    ->options(fn ($get) => Helpers::getStates($get('general.country')))
                                    ->searchable()
                                    ->reactive(),
                                Select::make('general.city')
                                    ->label(__('app.settings.fields.city'))
                                    ->options(fn ($get) => Helpers::getCities($get('general.state')))
                                    ->searchable()
                                    ->reactive(),
                                TextInput::make('general.zip')
                                    ->label(__('app.settings.fields.zip'))
                                    ->numeric()
                                    ->maxLength(10),
                            ]),
                    ])
                    ->columnSpan(3),
                Section::make(__('app.settings.sections.contact_information'))
                    ->aside()
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('general.gym_email')
                                    ->label(__('app.settings.fields.email_address'))
                                    ->email()
                                    ->prefixIcon('heroicon-o-envelope'),
                                TextInput::make('general.gym_contact')
                                    ->numeric()
                                    ->prefixIcon('heroicon-o-phone')
                                    ->label(__('app.settings.fields.contact_no')),
                            ]),
                    ])
                    ->columnSpan(3),
                Section::make('Check-In & Location Settings')
                    ->aside()
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('general.latitude')
                                    ->label('Gym Latitude')
                                    ->numeric()
                                    ->step('any')
                                    ->placeholder('e.g. 28.6139'),
                                TextInput::make('general.longitude')
                                    ->label('Gym Longitude')
                                    ->numeric()
                                    ->step('any')
                                    ->placeholder('e.g. 77.2090'),
                                TextInput::make('general.checkin_radius')
                                    ->label('Check-In Radius (meters)')
                                    ->numeric()
                                    ->default(100)
                                    ->placeholder('e.g. 100'),
                            ]),
                    ])
                    ->columnSpan(3),
            ]);
    }

    /**
     * Invoice Tab Schema.
     */
    private function invoiceTab(): Tab
    {
        return
            Tab::make(__('app.settings.tabs.invoice'))->icon('heroicon-m-document-text')
                ->schema([
                    Grid::make(3)
                        ->schema([
                            TextInput::make('invoice.prefix')
                                ->placeholder(__('app.settings.placeholders.prefix'))
                                ->label(__('app.settings.fields.prefix')),
                            TextInput::make('invoice.last_number')
                                ->numeric()
                                ->label(__('app.settings.fields.last_number'))
                                ->maxLength(10),
                            Select::make('invoice.name_type')
                                ->native(false)
                                ->label(__('app.settings.fields.name_type'))
                                ->options([
                                    'gym_name' => __('app.settings.options.name_type.gym_name'),
                                    'gym_logo' => __('app.settings.options.name_type.gym_logo'),
                                ]),
                        ]),
                    Fieldset::make(__('app.settings.sections.email'))
                        ->columns(['default' => 1, 'md' => 5])
                        ->schema([
                            Group::make()
                                ->schema([
                                    TextInput::make('notifications.email.invoice_subject_template')
                                        ->label(__('app.settings.fields.email_invoice_subject'))
                                        ->placeholder(__('app.settings.placeholders.invoice_email_subject'))
                                        ->helperText(__('app.settings.hints.tokens_invoice')),
                                    TextInput::make('notifications.email.receipt_subject_template')
                                        ->label(__('app.settings.fields.email_receipt_subject'))
                                        ->placeholder(__('app.settings.placeholders.receipt_email_subject'))
                                        ->helperText(__('app.settings.hints.tokens_receipt')),
                                ])->columnSpan(['default' => 1, 'md' => 3]),
                            Group::make()
                                ->schema([
                                    Toggle::make('notifications.email.enabled')
                                        ->label(__('app.settings.fields.email_enabled'))
                                        ->default(false)
                                        ->inlineLabel(),
                                    Toggle::make('notifications.email.auto_send_invoice_issued')
                                        ->label(__('app.settings.fields.auto_send_invoice_issued'))
                                        ->default(false)
                                        ->inlineLabel(),
                                    Toggle::make('notifications.email.auto_send_payment_receipt')
                                        ->label(__('app.settings.fields.auto_send_payment_receipt'))
                                        ->default(false)
                                        ->inlineLabel(),
                                ])
                                ->columns(1)
                                ->columnSpan(['default' => 1, 'md' => 2]),
                        ]),
                ]);
    }

    private function notificationTab(): Tab
    {
        return Tab::make('Notifications')
            ->icon('heroicon-m-bell')
            ->schema([
                Section::make('WhatsApp (MSG91)')
                    ->aside()
                    ->schema([
                        Grid::make(2)->schema([
                            Toggle::make('notifications.whatsapp.enabled')
                                ->label('Enable WhatsApp Notifications')
                                ->default(false)
                                ->inlineLabel(),
                            TextInput::make('notifications.whatsapp.integrated_number')
                                ->label('WhatsApp Integrated Number')
                                ->placeholder('91XXXXXXXXXX')
                                ->maxLength(20),
                            Toggle::make('notifications.whatsapp.send_welcome_message')
                                ->label('Send Welcome Message')
                                ->default(true)
                                ->inlineLabel(),
                            Toggle::make('notifications.whatsapp.send_payment_confirmation')
                                ->label('Send Payment Confirmation')
                                ->default(true)
                                ->inlineLabel(),
                            Toggle::make('notifications.whatsapp.send_payment_failed')
                                ->label('Send Payment Failed Alerts')
                                ->default(true)
                                ->inlineLabel(),
                            Toggle::make('notifications.whatsapp.send_expiry_warning')
                                ->label('Send Expiry Warning')
                                ->default(true)
                                ->inlineLabel(),
                            Toggle::make('notifications.whatsapp.send_renewal_reminders')
                                ->label('Send Renewal Reminders')
                                ->default(true)
                                ->inlineLabel(),
                            Select::make('notifications.whatsapp.renewal_reminder_days')
                                ->label('Reminder Days')
                                ->multiple()
                                ->options([
                                    1 => '1',
                                    2 => '2',
                                    3 => '3',
                                    5 => '5',
                                    7 => '7',
                                    10 => '10',
                                    14 => '14',
                                    30 => '30',
                                ])
                                ->default([7, 3, 1])
                                ->helperText('Days before expiry to send reminders.'),
                        ]),
                    ])->columnSpan(3),
                Section::make('SMS Fallback (MSG91)')
                    ->aside()
                    ->schema([
                        Grid::make(2)->schema([
                            Toggle::make('notifications.sms.enabled')
                                ->label('Enable SMS Fallback')
                                ->default(false)
                                ->inlineLabel(),
                            TextInput::make('notifications.sms.sender_id')
                                ->label('Sender ID')
                                ->helperText('6 chars, uppercase recommended')
                                ->maxLength(6)
                                ->formatStateUsing(fn ($state) => strtoupper((string) $state))
                                ->dehydrateStateUsing(fn ($state) => strtoupper((string) $state)),
                        ]),
                    ])->columnSpan(3),
                Section::make('Quiet Hours')
                    ->aside()
                    ->schema([
                        Grid::make(2)->schema([
                            TimePicker::make('notifications.whatsapp.quiet_hours_start')
                                ->label('Start Time')
                                ->seconds(false)
                                ->default('22:00')
                                ->helperText('Messages will not be sent during quiet hours'),
                            TimePicker::make('notifications.whatsapp.quiet_hours_end')
                                ->label('End Time')
                                ->seconds(false)
                                ->default('08:00'),
                        ]),
                    ])->columnSpan(3),
                Section::make('Test Notification')
                    ->aside()
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('notifications.whatsapp.test_phone')
                                ->label('Phone')
                                ->placeholder('91XXXXXXXXXX')
                                ->maxLength(20)
                                ->dehydrated(false),
                        ]),
                        Actions::make([
                            FormAction::make('sendTestMessage')
                                ->label('Send Test Message')
                                ->action(function (callable $get): void {
                                    $phone = (string) ($get('notifications.whatsapp.test_phone') ?? '');
                                    if (! filled($phone)) {
                                        Notification::make()
                                            ->title('Phone is required.')
                                            ->danger()
                                            ->send();

                                        return;
                                    }

                                    // We re-use the MSG91 service directly with a fake member instance.
                                    /** @var Msg91Service $msg91 */
                                    $msg91 = app(Msg91Service::class);
                                    $member = new Member([
                                        'name' => 'Test Member',
                                        'contact' => $phone,
                                        'whatsapp_enabled' => true,
                                        'sms_enabled' => true,
                                    ]);

                                    $ok = $msg91->sendWhatsApp($member, 'welcome', [
                                        'Test Member',
                                        (string) data_get(Helpers::getSettings(), 'general.gym_name', 'Gym'),
                                        'Test Plan',
                                        now()->addDays(30)->format('d-m-Y'),
                                    ]);

                                    $notification = Notification::make()
                                        ->title($ok ? 'Test message queued/sent.' : 'Test message failed (see Notification Logs).');

                                    if ($ok) {
                                        $notification->success();
                                    } else {
                                        $notification->danger();
                                    }

                                    $notification->send();
                                }),
                        ]),
                    ])->columnSpan(3),
            ]);
    }

    /**
     * Member Tab Schema.
     */
    private function memberTab(): Tab
    {
        return
            Tab::make(__('app.settings.tabs.member'))->icon('heroicon-m-user-group')
                ->schema([
                    Grid::make(2)
                        ->schema([
                            TextInput::make('member.prefix')
                                ->placeholder(__('app.settings.placeholders.prefix'))
                                ->label(__('app.settings.fields.prefix')),
                            TextInput::make('member.last_number')
                                ->numeric()
                                ->label(__('app.settings.fields.last_number'))
                                ->maxLength(10),
                        ]),
                ]);
    }

    /**
     * Charges Tab Schema.
     */
    private function chargesTab(): Tab
    {
        return
            Tab::make(__('app.settings.tabs.charges'))->icon('heroicon-m-currency-rupee')
                ->schema([
                    Grid::make(3)
                        ->schema([
                            TextInput::make('charges.admission_fee')
                                ->numeric()
                                ->label(__('app.settings.fields.admission_fee')),
                            TextInput::make('charges.taxes')
                                ->numeric()
                                ->label(__('app.settings.fields.taxes'))
                                ->suffix('%'),
                            TagsInput::make('charges.discounts')
                                ->label(__('app.settings.fields.discount_percent_available'))
                                ->hint(__('app.settings.hints.press_enter_to_add'))
                                ->placeholder(__('app.settings.hints.type_discount'))
                                ->separator(','),
                        ]),
                ]);
    }

    /**
     * Expenses Tab Schema.
     */
    private function expensesTab(): Tab
    {
        return
            Tab::make(__('app.settings.tabs.expenses'))->icon('heroicon-m-banknotes')
                ->schema([
                    TagsInput::make('expenses.categories')
                        ->label(__('app.settings.fields.categories'))
                        ->hint(__('app.settings.hints.press_enter_to_add'))
                        ->placeholder(__('app.settings.hints.type_category'))
                        ->separator(','),
                ]);
    }

    /**
     * Subscriptions Tab Schema.
     */
    private function subscriptionsTab(): Tab
    {
        return
            Tab::make(__('app.settings.tabs.subscriptions'))->icon('heroicon-m-ticket')
                ->schema([
                    TextInput::make('subscriptions.expiring_days')
                        ->label(__('app.settings.fields.expiring_days'))
                        ->numeric()
                        ->minValue(1)
                        ->default(7)
                        ->required(),
                ]);
    }

    /**
     * Configures a form instance by setting its schema and state path.
     *
     * @param  Schema  $schema  The form instance to configure.
     * @return Schema The configured form instance.
     */
    public function form(Schema $schema): Schema
    {
        return $schema
            ->components($this->getFormSchema())
            ->statePath('data');
    }

    /**
     * Persist the current settings.
     */
    public function save(): void
    {
        $settings = $this->data ?? [];
        $general = is_array($settings['general'] ?? null) ? $settings['general'] : [];

        if (! empty($general['financial_year_start']) && is_string($general['financial_year_start'])) {
            $general['financial_year_start'] =
                Carbon::parse($general['financial_year_start'])
                    ->toDateString();
        }
        if (! empty($general['financial_year_end']) && is_string($general['financial_year_end'])) {
            $general['financial_year_end'] =
                Carbon::parse($general['financial_year_end'])
                    ->toDateString();
        }

        foreach (['gym_logo'] as $logoKey) {
            $value = $general[$logoKey] ?? null;
            if (is_array($value)) {
                $general[$logoKey] = $value[0] ?? null;
            }
        }

        $settings['general'] = $general;

        try {
            app(SettingsRepository::class)->put($settings);
            $this->data = $settings;
        } catch (\Throwable $exception) {
            report($exception);

            Notification::make()
                ->title(__('app.notifications.failed'))
                ->body(__('app.notifications.failed_settings_save'))
                ->danger()
                ->send();

            return;
        }

        Notification::make()
            ->title(__('app.notifications.success'))
            ->body(__('app.notifications.success_settings_save'))
            ->success()
            ->send();
    }

    /**
     * Handles the file upload process and updates the settings data.
     *
     * @param  TemporaryUploadedFile|string|null  $state  The uploaded file state.
     * @param  string  $key  The key to store the uploaded file path in the settings.
     * @param  callable  $set  The callback to update the form state.
     */
    private function handleFileUpload(mixed $state, string $key, callable $set): void
    {
        if (! $state instanceof TemporaryUploadedFile) {
            return;
        }

        $path = $state->storeAs('images', $state->getClientOriginalName(), 'public');
        $repository = app(SettingsRepository::class);
        $settings = $repository->get();
        $general = is_array($settings['general'] ?? null) ? $settings['general'] : [];

        $general[$key] = $path;
        $settings['general'] = $general;

        $repository->put($settings);

        // Update the form state
        $set("general.$key", [$path]);
    }
}
