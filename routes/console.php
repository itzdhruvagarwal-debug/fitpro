<?php

use App\Mail\GymPlanExpiringMail;
use App\Models\Gym;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Mark subscriptions expired every day at 00:00
Schedule::command('gymie:subscriptions')
    ->dailyAt('00:00')
    ->withoutOverlapping()
    ->onOneServer();

// Mark invoices overdue every day at 00:00
Schedule::command('gymie:invoices --mark-overdue')
    ->dailyAt('00:00')
    ->withoutOverlapping()
    ->onOneServer();

// Send renewal reminders every day at 09:00
Schedule::command('notifications:send-renewal-reminders')
    ->dailyAt('09:00')
    ->withoutOverlapping()
    ->onOneServer();

// Clean old backups daily at 01:00
Schedule::command('backup:clean')
    ->dailyAt('01:00')
    ->withoutOverlapping()
    ->onOneServer();

// Run database and file backup daily at 02:00
Schedule::command('backup:run')
    ->dailyAt('02:00')
    ->withoutOverlapping()
    ->onOneServer();

// Prune prunable and mass-prunable models daily
Schedule::command('model:prune')
    ->daily()
    ->withoutOverlapping()
    ->onOneServer();

// Send Gym Subscription Expiry email and WhatsApp reminders daily at 09:00
Schedule::call(function () {
    $gyms = Gym::query()->withoutGlobalScopes()->where('status', '!=', 'suspended')->get();

    foreach ($gyms as $gym) {
        if ($gym->plan_expires_at) {
            $diffInDays = (int) now()->startOfDay()->diffInDays($gym->plan_expires_at->startOfDay(), false);

            if (in_array($diffInDays, [7, 3, 1, 0], true)) {
                // Send Email
                if ($gym->owner_email) {
                    Mail::to($gym->owner_email)->send(new GymPlanExpiringMail($gym, $diffInDays));
                }

                // Send WhatsApp
                if ($gym->owner_phone) {
                    try {
                        /** @var \App\Services\Msg91Service $msg91 */
                        $msg91 = app(\App\Services\Msg91Service::class);
                        $renewalLink = url('/admin/subscription-billing');
                        $msg91->sendWhatsApp(
                            $gym->owner_phone,
                            $diffInDays <= 0 ? 'membership_expired' : 'expiry_warning',
                            [
                                (string) ($gym->owner_name ?: $gym->name),
                                'GymSaathi',
                                (string) ($gym->plan ?: 'Trial'),
                                $renewalLink,
                            ]
                        );
                    } catch (\Throwable $e) {
                        Log::error("Failed to send gym subscription WhatsApp reminder: ".$e->getMessage());
                    }
                }
            }
        }
    }
})->dailyAt('09:00')
    ->name('send-gym-renewal-reminders')
    ->onOneServer();
