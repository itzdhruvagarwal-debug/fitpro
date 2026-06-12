<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Gym Subscription Status Header Card -->
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-gray-900 via-slate-800 to-zinc-900 p-8 shadow-xl border border-gray-700 text-white">
            <div class="absolute -right-10 -top-10 h-40 w-40 rounded-full bg-violet-600/20 blur-3xl"></div>
            <div class="absolute -bottom-10 -left-10 h-40 w-40 rounded-full bg-blue-600/10 blur-3xl"></div>
            
            <div class="relative z-10 md:flex md:items-center md:justify-between">
                <div>
                    <h3 class="text-sm font-semibold tracking-wider text-violet-400 uppercase">Current Plan</h3>
                    <h2 class="mt-1 text-3xl font-extrabold tracking-tight">
                        {{ strtoupper($gym->plan ?: 'Trial') }}
                    </h2>
                    <p class="mt-3 text-sm text-gray-400">
                        @if($gym->plan_expires_at)
                            Your plan will automatically renew on <span class="font-semibold text-white">{{ $gym->plan_expires_at->format('M d, Y') }}</span>
                            @if($gym->plan_expires_at->isPast())
                                <span class="ml-2 inline-flex items-center rounded-md bg-red-400/10 px-2 py-1 text-xs font-medium text-red-400 ring-1 ring-inset ring-red-400/20">Expired</span>
                            @else
                                <span class="ml-2 text-violet-300">({{ now()->diffInDays($gym->plan_expires_at) }} days left)</span>
                            @endif
                        @elseif($gym->isOnTrial())
                            Your free trial is active and expires on <span class="font-semibold text-white">{{ $gym->trial_ends_at->format('M d, Y') }}</span>
                            <span class="ml-2 text-violet-300">({{ now()->diffInDays($gym->trial_ends_at) }} days left)</span>
                        @else
                            No active plan detected. Please select a plan below to activate your account.
                        @endif
                    </p>
                </div>
                
                <div class="mt-6 md:mt-0 flex items-center gap-4">
                    @if($gym->razorpay_mandate_status === 'active')
                        <div class="flex items-center gap-2 rounded-full bg-emerald-500/10 px-4 py-2 text-sm font-semibold text-emerald-400 border border-emerald-500/20">
                            <span class="h-2 w-2 rounded-full bg-emerald-400 animate-pulse"></span>
                            AutoPay Active
                        </div>
                    @else
                        <div class="flex items-center gap-2 rounded-full bg-amber-500/10 px-4 py-2 text-sm font-semibold text-amber-400 border border-amber-500/20">
                            Manual Billing / Mandate Inactive
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Section Title -->
        <div class="text-center py-4">
            <h2 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Upgrade or Manage Subscription</h2>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Choose the perfect plan to scale your fitness center operations.</p>
        </div>

        <!-- Pricing Cards Grid -->
        <div class="grid grid-cols-1 gap-8 md:grid-cols-3">
            @foreach($pricingPlans as $plan)
                <div class="relative flex flex-col rounded-3xl bg-white dark:bg-gray-800 p-8 shadow-lg ring-1 ring-gray-200 dark:ring-gray-700 transition duration-300 hover:shadow-2xl hover:scale-[1.02] {{ $gym->plan === $plan['name'] ? 'ring-2 ring-violet-500 dark:ring-violet-500' : '' }}">
                    @if($gym->plan === $plan['name'])
                        <div class="absolute -top-4 left-1/2 -translate-x-1/2 rounded-full bg-violet-600 px-4 py-1 text-xs font-semibold uppercase tracking-wider text-white shadow-md">
                            Active Plan
                        </div>
                    @endif
                    
                    <div class="mb-6">
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white">{{ $plan['label'] }}</h3>
                        <p class="mt-4 flex items-baseline text-gray-900 dark:text-white">
                            <span class="text-4xl font-extrabold tracking-tight">₹{{ number_format($plan['price']) }}</span>
                            <span class="ml-1 text-sm font-semibold text-gray-500">/month</span>
                        </p>
                    </div>

                    <!-- Features List -->
                    <ul role="list" class="mt-4 space-y-4 flex-1">
                        @foreach($plan['features'] as $feature)
                            <li class="flex items-start gap-3 text-sm text-gray-600 dark:text-gray-300">
                                <svg class="h-5 w-5 shrink-0 text-violet-500" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                </svg>
                                <span>{{ $feature }}</span>
                            </li>
                        @endforeach
                    </ul>

                    <!-- Call To Action Button -->
                    <div class="mt-8">
                        <button 
                            wire:click="subscribe('{{ $plan['name'] }}')"
                            wire:loading.attr="disabled"
                            class="w-full rounded-2xl bg-gradient-to-r {{ $plan['color'] }} px-4 py-3 text-center text-sm font-bold text-white shadow-md hover:opacity-90 focus:outline-none transition duration-200 disabled:opacity-50"
                        >
                            <span wire:loading.remove wire:target="subscribe('{{ $plan['name'] }}')">
                                @if($gym->plan === $plan['name'])
                                    Renew Plan
                                @else
                                    Select & Subscribe
                                @endif
                            </span>
                            <span wire:loading wire:target="subscribe('{{ $plan['name'] }}')">
                                Processing...
                            </span>
                        </button>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Razorpay Checkout Integration -->
    <script src="https://checkout.razorpay.com/v1/checkout.js" defer></script>
    <script>
        document.addEventListener('livewire:init', () => {
            Livewire.on('open-gym-razorpay-checkout', (data) => {
                const config = data.config;
                const options = {
                    key: config.razorpay_key,
                    subscription_id: config.subscription_id,
                    name: 'GymSaathi Platform',
                    description: 'Subscription for ' + config.plan_name.toUpperCase() + ' Plan',
                    prefill: {
                        name: config.owner_name || config.gym_name,
                        email: config.owner_email,
                        contact: config.owner_phone
                    },
                    handler: function (response) {
                        // Pass the response token details to verify it on Livewire backend
                        Livewire.dispatch('verify-gym-subscription', { 
                            response: response, 
                            planName: config.plan_name 
                        });
                    },
                    theme: {
                        color: '#7c3aed' // violet theme color
                    }
                };
                
                const rzp = new Razorpay(options);
                rzp.open();
            });
            
            // Listen to verification request dispatched by checkout handler
            Livewire.on('verify-gym-subscription', (data) => {
                @this.call('verifySubscription', data.response, data.planName);
            });
        });
    </script>
</x-filament-panels::page>
