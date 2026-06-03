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
    ->name('razorpay.webhook');
