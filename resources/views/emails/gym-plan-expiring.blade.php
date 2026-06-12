# Hello, {{ $gym->owner_name ?: 'Gym Owner' }}

This is a reminder regarding your subscription for **{{ $gym->name }}** on the **GymSaathi** platform.

@if($daysLeft <= 0)
## Your subscription has EXPIRED!

Your access to the admin dashboard has been suspended. To restore access and continue managing your gym members, payments, and invoices, please select a plan and renew your subscription.
@else
## Your subscription is expiring soon!

You have **{{ $daysLeft }} days left** before your current subscription plan expires on **{{ $gym->plan_expires_at->format('M d, Y') }}**.
@endif

### Why keep your subscription active?
* Manage members, plans, and subscriptions seamlessly.
* Fast full-text member search and automated GST invoicing.
* Log expenses, follow-ups, and enquiries.
* Auto-send welcome, invoice, and payment receipt alerts via WhatsApp & SMS.

To prevent any service interruption, please log in to your dashboard and renew your plan or set up AutoPay:

<x-mail::button :url="url('/admin/subscription-billing')">
Manage Subscription
</x-mail::button>

If you have already set up AutoPay, no action is required; your account will renew automatically.

Thanks,  
**The GymSaathi Team**
