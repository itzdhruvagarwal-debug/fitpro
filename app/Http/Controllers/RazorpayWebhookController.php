<?php

namespace App\Http\Controllers;

use App\Jobs\HandleFailedPayment;
use App\Jobs\ProcessGymSubscriptionWebhook;
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

            return response()->json(['message' => 'Invalid webhook signature.'], 401);
        }

        $payload = $request->json()->all();
        $event = (string) data_get($payload, 'event');
        $payment = data_get($payload, 'payload.payment.entity', []);
        $subscription = data_get($payload, 'payload.subscription.entity', []);
        $gymId = app()->bound('currentTenant') && app('currentTenant')
            ? (int) app('currentTenant')->id
            : null;

        DB::table('razorpay_webhook_events')->insertOrIgnore([
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

        $webhookEvent = DB::table('razorpay_webhook_events')
            ->where('payload_hash', $payloadHash)
            ->first(['id', 'processed_at']);

        if (! $webhookEvent) {
            Log::error('Razorpay webhook event could not be recorded.', [
                'event' => $event,
            ]);

            return response()->json(['message' => 'Webhook could not be recorded.'], 500);
        }

        if ($webhookEvent->processed_at !== null) {
            return response()->json(['ok' => true, 'duplicate' => true], 200);
        }

        $isGymSubscription = (bool) (data_get($payment, 'notes.is_gym_subscription')
            ?: data_get($subscription, 'notes.is_gym_subscription'));

        if ($isGymSubscription) {
            try {
                ProcessGymSubscriptionWebhook::dispatch($event, $payload);
            } catch (\Throwable $exception) {
                Log::error('Razorpay gym webhook event processing failed.', [
                    'event' => $event,
                    'exception' => $exception->getMessage(),
                ]);

                return response()->json(['message' => 'Webhook processing failed.'], 500);
            }

            DB::table('razorpay_webhook_events')
                ->where('id', $webhookEvent->id)
                ->whereNull('processed_at')
                ->update([
                    'processed_at' => now(),
                    'updated_at' => now(),
                ]);

            return response()->json(['ok' => true], 200);
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
            Log::error('Razorpay webhook event processing failed.', [
                'event' => $event,
                'exception' => $exception->getMessage(),
            ]);

            return response()->json(['message' => 'Webhook processing failed.'], 500);
        }

        DB::table('razorpay_webhook_events')
            ->where('id', $webhookEvent->id)
            ->whereNull('processed_at')
            ->update([
                'processed_at' => now(),
                'updated_at' => now(),
            ]);

        return response()->json(['ok' => true], 200);
    }
}
