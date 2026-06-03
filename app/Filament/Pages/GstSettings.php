<?php

namespace App\Filament\Pages;

use App\Contracts\SettingsRepository;
use BackedEnum;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class GstSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $title = 'GST Settings';

    protected static ?string $navigationLabel = 'GST Settings';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-receipt-percent';

    protected string $view = 'filament.pages.gst-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $settings = app(SettingsRepository::class)->get();
        $this->data = is_array($settings['gst'] ?? null) ? $settings['gst'] : [];
        $this->form->fill($this->data);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('GST Configuration')
                    ->schema([
                        Toggle::make('enabled')
                            ->label('GST Registered')
                            ->default(false),
                        TextInput::make('gym_gstin')
                            ->label('GSTIN')
                            ->regex('/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/')
                            ->maxLength(15),
                        TextInput::make('gym_pan')
                            ->label('PAN')
                            ->regex('/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/')
                            ->maxLength(10),
                        TextInput::make('gym_legal_name')
                            ->label('Legal Name'),
                        TextInput::make('gst_rate')
                            ->numeric()
                            ->default(18),
                    ])->columns(2),
                Section::make('Business Address')
                    ->schema([
                        TextInput::make('gym_address_line1')->label('Address Line 1'),
                        TextInput::make('gym_address_line2')->label('Address Line 2'),
                        TextInput::make('gym_city')->label('City'),
                        TextInput::make('gym_state')->label('State'),
                        TextInput::make('gym_pincode')->label('Pincode')->maxLength(6),
                        Select::make('gym_state_code')
                            ->label('State Code')
                            ->options($this->stateCodes()),
                    ])->columns(2),
                Section::make('Invoice Settings')
                    ->schema([
                        TextInput::make('invoice_prefix')
                            ->label('Invoice Prefix')
                            ->default('GSA')
                            ->maxLength(5)
                            ->formatStateUsing(fn ($state) => strtoupper((string) $state)),
                        TextInput::make('invoice_counter')
                            ->label('Current Counter')
                            ->numeric()
                            ->default(1),
                        Placeholder::make('next_preview')
                            ->label('Preview')
                            ->content(function (callable $get): string {
                                $prefix = strtoupper((string) ($get('invoice_prefix') ?: 'GSA'));
                                $counter = str_pad((string) ((int) ($get('invoice_counter') ?: 1)), 5, '0', STR_PAD_LEFT);

                                return 'Next invoice will be: '.$prefix.'-'.now()->year.'-'.$counter;
                            }),
                    ])->columns(2),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $settings = app(SettingsRepository::class)->get();
        $settings['gst'] = [
            ...($settings['gst'] ?? []),
            ...($this->data ?? []),
            'invoice_prefix' => strtoupper((string) ($this->data['invoice_prefix'] ?? 'GSA')),
        ];
        app(SettingsRepository::class)->put($settings);

        Notification::make()
            ->title('GST settings saved.')
            ->success()
            ->send();
    }

    /**
     * @return array<string, string>
     */
    private function stateCodes(): array
    {
        return [
            '01' => '01 - Jammu & Kashmir', '02' => '02 - Himachal Pradesh', '03' => '03 - Punjab',
            '04' => '04 - Chandigarh', '05' => '05 - Uttarakhand', '06' => '06 - Haryana',
            '07' => '07 - Delhi', '08' => '08 - Rajasthan', '09' => '09 - Uttar Pradesh',
            '10' => '10 - Bihar', '11' => '11 - Sikkim', '12' => '12 - Arunachal Pradesh',
            '13' => '13 - Nagaland', '14' => '14 - Manipur', '15' => '15 - Mizoram',
            '16' => '16 - Tripura', '17' => '17 - Meghalaya', '18' => '18 - Assam',
            '19' => '19 - West Bengal', '20' => '20 - Jharkhand', '21' => '21 - Odisha',
            '22' => '22 - Chhattisgarh', '23' => '23 - Madhya Pradesh', '24' => '24 - Gujarat',
            '26' => '26 - Dadra and Nagar Haveli and Daman and Diu', '27' => '27 - Maharashtra',
            '28' => '28 - Andhra Pradesh', '29' => '29 - Karnataka', '30' => '30 - Goa',
            '31' => '31 - Lakshadweep', '32' => '32 - Kerala', '33' => '33 - Tamil Nadu',
            '34' => '34 - Puducherry', '35' => '35 - Andaman and Nicobar Islands',
            '36' => '36 - Telangana', '37' => '37 - Andhra Pradesh (New)',
            '38' => '38 - Ladakh',
        ];
    }
}
