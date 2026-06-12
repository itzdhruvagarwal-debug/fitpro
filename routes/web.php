<?php

use App\Http\Controllers\GymRegistrationController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\InvoiceDocumentController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\RazorpayWebhookController;
use Filament\Http\Middleware\Authenticate;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->to(app()->bound('currentTenant') && app('currentTenant')
        ? '/admin'
        : route('gym.register.form'));
})->name('home');

Route::get('/register', [GymRegistrationController::class, 'showRegistrationForm'])->name('gym.register.form');
Route::post('/register', [GymRegistrationController::class, 'register'])
    ->middleware('throttle:api-login')
    ->name('gym.register');

Route::middleware([Authenticate::class, 'tenant.active', 'permissions.team', 'tenant.user'])
    ->group(function (): void {
        Route::get('/invoices/{invoice}/preview', [InvoiceDocumentController::class, 'preview'])
            ->name('invoices.preview');

        Route::get('/invoices/{invoice}/download', [InvoiceDocumentController::class, 'download'])
            ->name('invoices.download');
        Route::get('/invoices/{invoice}/gst-download', [InvoiceController::class, 'download'])
            ->name('invoice.download');
        Route::post('/invoices/{invoice}/send', [InvoiceController::class, 'sendEmail'])
            ->name('invoice.send');

    });

Route::middleware(['auth', 'tenant.active', 'permissions.team', 'tenant.user'])
    ->prefix('payment')
    ->group(function (): void {
        Route::post('/subscribe/{member}', [PaymentController::class, 'initiateSubscription'])
            ->name('payment.subscribe');
        Route::post('/order/{member}', [PaymentController::class, 'initiateOrder'])
            ->name('payment.order');
        Route::post('/verify', [PaymentController::class, 'verifyPayment'])
            ->name('payment.verify');
        Route::post('/cancel/{member}', [PaymentController::class, 'cancelSubscription'])
            ->name('payment.cancel');
    });

Route::post('/razorpay/webhook', [RazorpayWebhookController::class, 'handle'])
    ->middleware('throttle:razorpay-webhook')
    ->name('razorpay.webhook');

// Member Routes
Route::prefix('member')->name('member.')->middleware(['tenant.active'])->group(function (): void {
    Route::middleware(['guest.member'])->group(function (): void {
        Route::get('/login', [\App\Http\Controllers\Member\MemberAuthController::class, 'showLogin'])->name('login');
        Route::post('/login', [\App\Http\Controllers\Member\MemberAuthController::class, 'login'])->name('login.post');
    });

    Route::middleware(['auth.member'])->group(function (): void {
        Route::get('/dashboard', [\App\Http\Controllers\Member\MemberDashboardController::class, 'index'])->name('dashboard');
        Route::post('/logout', [\App\Http\Controllers\Member\MemberAuthController::class, 'logout'])->name('logout');
        
        // Attendance
        Route::post('/check-in', [\App\Http\Controllers\Member\MemberDashboardController::class, 'checkIn'])->name('checkin');

        // Payments
        Route::post('/payment/subscribe', [\App\Http\Controllers\Member\MemberDashboardController::class, 'initiateSubscription'])->name('payment.subscribe');
        Route::post('/payment/order', [\App\Http\Controllers\Member\MemberDashboardController::class, 'initiateOrder'])->name('payment.order');
        Route::post('/payment/verify', [\App\Http\Controllers\Member\MemberDashboardController::class, 'verifyPayment'])->name('payment.verify');

        // Invoices
        Route::get('/invoice/{invoice}/download', [\App\Http\Controllers\Member\MemberDashboardController::class, 'downloadInvoice'])->name('invoice.download');
    });
});

