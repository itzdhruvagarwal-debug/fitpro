<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\PaymentTransaction;
use App\Models\Plan;
use App\Rules\ModelExists;
use App\Services\RazorpayService;
use App\Support\Data;
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
            'plan_id' => ['required', 'integer', new ModelExists(Plan::class)],
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

        $tenantId = app()->bound('currentTenant') && app('currentTenant')
            ? Data::int(app('currentTenant')->id)
            : null;

        $transaction = $this->findPaymentTransaction($data, $tenantId);
        if (! $transaction) {
            return response()->json(['success' => false, 'message' => 'Transaction not found'], 404);
        }

        $member = $transaction->member()->first();
        if (! $member) {
            return response()->json(['success' => false, 'message' => 'Member not found'], 404);
        }

        $this->authorize('update', $member);

        $result = DB::transaction(function () use ($data, $transaction, $member): array {
            $transaction = PaymentTransaction::query()
                ->whereKey($transaction->getKey())
                ->lockForUpdate()
                ->first();

            if (! $transaction || (int) $transaction->member_id !== (int) $member->id) {
                return ['code' => 404, 'payload' => ['success' => false, 'message' => 'Transaction not found']];
            }

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

    /**
     * @param  array<string, mixed>  $data
     */
    private function findPaymentTransaction(array $data, ?int $tenantId): ?PaymentTransaction
    {
        $query = PaymentTransaction::query()
            ->where('razorpay_payment_id', $data['razorpay_payment_id']);

        if ($tenantId !== null) {
            $query->where('gym_id', $tenantId);
        }

        $transaction = $query->latest('id')->first();

        if ($transaction || blank($data['razorpay_order_id'] ?? null)) {
            return $transaction;
        }

        $query = PaymentTransaction::query()
            ->where('razorpay_order_id', $data['razorpay_order_id']);

        if ($tenantId !== null) {
            $query->where('gym_id', $tenantId);
        }

        return $query->latest('id')->first();
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
