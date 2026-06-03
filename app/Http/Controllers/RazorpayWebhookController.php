<?php

namespace App\Http\Controllers;

use App\Jobs\HandleFailedPayment;
use App\Jobs\ProcessRazorpayPayment;
use App\Jobs\ProcessRazorpaySubscriptionEvent;
use App\Services\RazorpayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RazorpayWebhookController extends Controller
{
    public function __construct(private readonly RazorpayService $razorpayService) {}

    public function handle(Request $request): JsonResponse
    {
        $body = (string) $request->getContent();
        $signature = (string) $request->header('X-Razorpay-Signature', '');
        $payloadHash = hash('sha256', $body);

        if (! $this->razorpayService->verifyWebhookSignature($body, $signature)) {
            Log::warning('Razorpay webhook signature verification failed.');

            return response()->json(['ok' => true], 200);
        }

        $payload = $request->json()->all();
        $event = (string) data_get($payload, 'event');
        $payment = data_get($payload, 'payload.payment.entity', []);
        $subscription = data_get($payload, 'payload.subscription.entity', []);
        $gymId = app()->bound('currentTenant') && app('currentTenant')
            ? (int) app('currentTenant')->id
            : null;

        $inserted = DB::table('razorpay_webhook_events')->insertOrIgnore([
            'gym_id' => $gymId,
            'event_name' => $event !== '' ? $event : 'unknown',
            'razorpay_event_id' => data_get($payload, 'payload.payment.entity.id')
                ?: data_get($payload, 'payload.subscription.entity.id'),
            'razorpay_payment_id' => data_get($payment, 'id'),
            'razorpay_order_id' => data_get($payment, 'order_id'),
            'razorpay_subscription_id' => data_get($payment, 'subscription_id') ?: data_get($subscription, 'id'),
            'signature_hash' => $signature !== '' ? hash('sha256', $signature) : null,
            'payload_hash' => $payloadHash,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if ($inserted === 0) {
            return response()->json(['ok' => true, 'duplicate' => true], 200);
        }

        try {
            match ($event) {
                'payment.captured' => ProcessRazorpayPayment::dispatch($payload),
                'payment.failed' => HandleFailedPayment::dispatch($payload),
                'subscription.activated' => ProcessRazorpaySubscriptionEvent::dispatch($payload, true, 'active'),
                'subscription.charged' => ProcessRazorpayPayment::dispatch($payload),
                'subscription.halted' => ProcessRazorpaySubscriptionEvent::dispatch($payload, true, 'halted'),
                'subscription.cancelled' => ProcessRazorpaySubscriptionEvent::dispatch($payload, false, 'cancelled'),
                default => null,
            };
        } catch (\Throwable $exception) {
            Log::warning('Razorpay webhook event processing failed.', [
                'event' => $event,
            ]);
        }

        DB::table('razorpay_webhook_events')
            ->where('payload_hash', $payloadHash)
            ->update([
                'processed_at' => now(),
                'updated_at' => now(),
            ]);

        return response()->json(['ok' => true], 200);
    }
}
