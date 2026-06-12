<?php

namespace App\Filament\Pages;

use App\Models\Gym;
use App\Services\RazorpayService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class SubscriptionBilling extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-credit-card';

    protected string $view = 'filament.pages.subscription-billing';

    public ?Gym $gym = null;

    /**
     * @var list<array{
     *  name: string,
     *  label: string,
     *  price: int,
     *  features: list<string>,
     *  color: string
     * }>
     */
    public array $pricingPlans = [];

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->gym = app('currentTenant');

        $this->pricingPlans = [
            [
                'name' => 'starter',
                'label' => 'Starter Plan',
                'price' => 999,
                'features' => [
                    'Up to 200 active members',
                    'Basic invoicing & receipts',
                    'Standard analytics & reports',
                    'Single user login access',
                ],
                'color' => 'from-blue-500 to-indigo-600',
            ],
            [
                'name' => 'growth',
                'label' => 'Growth Plan',
                'price' => 1999,
                'features' => [
                    'Up to 1,000 active members',
                    'Full GST calculation & reports',
                    'WhatsApp & SMS integrations',
                    'Up to 5 staff user logins',
                ],
                'color' => 'from-amber-500 to-orange-600',
            ],
            [
                'name' => 'pro',
                'label' => 'Pro Plan',
                'price' => 3999,
                'features' => [
                    'Unlimited active members',
                    'Priority support & setup',
                    'Unlimited staff user logins',
                    'Advanced automated reminders',
                ],
                'color' => 'from-violet-500 to-purple-600',
            ],
        ];
    }

    /**
     * Get page title.
     */
    public function getTitle(): string
    {
        return 'Subscription & Billing';
    }

    /**
     * Get navigation label.
     */
    public static function getNavigationLabel(): string
    {
        return 'Subscription';
    }

    /**
     * Start subscription flow with Razorpay.
     *
     * @return array<string, mixed>|null
     */
    public function subscribe(string $planName): ?array
    {
        try {
            /** @var RazorpayService $razorpay */
            $razorpay = app(RazorpayService::class);

            // Create a gym subscription mandate on Razorpay
            $checkoutData = $razorpay->createGymSubscription($this->gym, $planName);

            // Dispatch browser event to open checkout in frontend
            $this->dispatch('open-gym-razorpay-checkout', config: $checkoutData + [
                'plan_name' => $planName,
                'gym_name' => $this->gym->name,
                'owner_name' => $this->gym->owner_name,
                'owner_email' => $this->gym->owner_email,
                'owner_phone' => $this->gym->owner_phone,
            ]);

            return $checkoutData;
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Unable to start checkout')
                ->body($e->getMessage())
                ->danger()
                ->send();

            return null;
        }
    }

    /**
     * Verify payment signature locally after modal success.
     */
    public function verifySubscription(array $response, string $planName): void
    {
        /** @var RazorpayService $razorpay */
        $razorpay = app(RazorpayService::class);

        $verified = $razorpay->verifyPaymentSignature([
            'razorpay_subscription_id' => $response['razorpay_subscription_id'],
            'razorpay_payment_id' => $response['razorpay_payment_id'],
            'razorpay_signature' => $response['razorpay_signature'],
        ]);

        if (! $verified) {
            Notification::make()
                ->title('Payment verification failed')
                ->body('The payment signature was invalid.')
                ->danger()
                ->send();

            return;
        }

        // Lock database updates for landlord operations
        $this->gym->update([
            'plan' => $planName,
            'status' => 'active',
            'plan_expires_at' => now()->addDays(30), // Initial extension, webhook handles exact charge updates
            'razorpay_subscription_id' => $response['razorpay_subscription_id'],
            'razorpay_mandate_status' => 'active',
        ]);

        Notification::make()
            ->title('Subscription Activated!')
            ->body('Thank you! Your '.ucfirst($planName).' plan is now active.')
            ->success()
            ->send();

        // Refresh gym state
        $this->gym = Gym::query()->withoutGlobalScopes()->find($this->gym->id);
    }
}
