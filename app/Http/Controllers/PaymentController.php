<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\PaymentTransaction;
use App\Models\Plan;
use App\Services\RazorpayService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    public function __construct(private readonly RazorpayService $razorpayService) {}

    public function initiateSubscription(Request $request, Member $member): JsonResponse
    {
        $this->authorize('update', $member);

        $data = $request->validate([
            'plan_id' => ['required', 'integer', 'exists:plans,id'],
        ]);

        $plan = Plan::query()->findOrFail((int) $data['plan_id']);
        $subscription = $this->razorpayService->createSubscription($member, $plan);

        return response()->json([
            'success' => true,
            'data' => $subscription + [
                'member_name' => $member->name,
                'member_email' => $member->email,
                'member_phone' => $member->contact,
                'description' => 'UPI AutoPay mandate setup',
            ],
        ]);
    }

    public function initiateOrder(Request $request, Member $member): JsonResponse
    {
        $this->authorize('update', $member);

        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:1'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $order = $this->razorpayService->createOrder(
            $member,
            (float) $data['amount'],
            (string) ($data['description'] ?? ''),
        );

        return response()->json([
            'success' => true,
            'data' => $order + [
                'member_name' => $member->name,
                'member_email' => $member->email,
                'member_phone' => $member->contact,
                'description' => (string) ($data['description'] ?? 'Membership Payment'),
            ],
        ]);
    }

    public function verifyPayment(Request $request): JsonResponse
    {
        $data = $request->validate([
            'razorpay_order_id' => ['nullable', 'string'],
            'razorpay_payment_id' => ['required', 'string'],
            'razorpay_signature' => ['required', 'string'],
        ]);

        $verified = $this->razorpayService->verifyPaymentSignature($data);
        if (! $verified) {
            return response()->json(['success' => false, 'message' => 'Invalid payment signature'], 422);
        }

        $result = DB::transaction(function () use ($data): array {
            $transaction = PaymentTransaction::query()
                ->where('razorpay_payment_id', $data['razorpay_payment_id'])
                ->lockForUpdate()
                ->latest('id')
                ->first();

            if (! $transaction && filled($data['razorpay_order_id'] ?? null)) {
                $transaction = PaymentTransaction::query()
                    ->where('razorpay_order_id', $data['razorpay_order_id'])
                    ->lockForUpdate()
                    ->latest('id')
                    ->first();
            }

            if (! $transaction) {
                return ['code' => 404, 'payload' => ['success' => false, 'message' => 'Transaction not found']];
            }

            $member = $transaction->member()->first();
            if (! $member) {
                return ['code' => 404, 'payload' => ['success' => false, 'message' => 'Member not found']];
            }

            $this->authorize('update', $member);

            if (
                filled($transaction->razorpay_payment_id)
                && $transaction->razorpay_payment_id !== $data['razorpay_payment_id']
            ) {
                return ['code' => 409, 'payload' => ['success' => false, 'message' => 'Transaction/payment mismatch']];
            }

            if ($transaction->status === 'captured') {
                return ['code' => 200, 'payload' => ['success' => true, 'already_captured' => true]];
            }

            $transaction->update([
                'razorpay_payment_id' => $data['razorpay_payment_id'],
                'status' => 'captured',
                'paid_at' => Carbon::now(),
            ]);

            $days = (int) ($member->subscriptions()->latest('end_date')->with('plan')->first()?->plan?->days ?? 30);
            $member->extendMembership($days);

            return ['code' => 200, 'payload' => ['success' => true]];
        });

        return response()->json($result['payload'], $result['code']);
    }

    public function cancelSubscription(Member $member): JsonResponse
    {
        $this->authorize('update', $member);

        if (! $member->razorpay_subscription_id) {
            return response()->json(['success' => false, 'message' => 'Subscription not linked'], 422);
        }

        $this->razorpayService->cancelSubscription($member->razorpay_subscription_id);

        $member->update([
            'upi_autopay_active' => false,
            'upi_mandate_status' => 'cancelled',
        ]);

        return response()->json(['success' => true]);
    }
}
