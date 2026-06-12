<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Helpers\Helpers;
use App\Models\Invoice;
use App\Models\MemberAttendance;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\PaymentTransaction;
use App\Services\GstInvoiceService;
use App\Services\RazorpayService;
use App\Support\AppConfig;
use App\Enums\Status;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class MemberDashboardController extends Controller
{
    public function index(Request $request)
    {
        /** @var \App\Models\Member $member */
        $member = Auth::guard('member')->user();
        $gym = app('currentTenant');
        $settings = Helpers::getSettings();

        // Get active subscription
        $activeSubscription = $member->subscriptions()
            ->whereIn('status', [Status::Ongoing, Status::Active, Status::Expiring])
            ->latest('end_date')
            ->first();

        // Get plans available for subscription/purchase
        $plans = Plan::where('status', Status::Active)
            ->get();

        // Check today's attendance status
        $timezone = AppConfig::timezone();
        $today = Carbon::today($timezone);
        
        $todayAttendance = $member->attendances()
            ->whereDate('checked_in_at', $today)
            ->first();

        // Stats
        $totalCheckins = $member->attendances()->count();

        // Invoice list
        $invoices = Invoice::whereHas('subscription', function ($q) use ($member) {
                $q->where('member_id', $member->id);
            })
            ->latest('date')
            ->get();

        return view('member.dashboard', compact(
            'member',
            'gym',
            'settings',
            'activeSubscription',
            'plans',
            'todayAttendance',
            'totalCheckins',
            'invoices'
        ));
    }

    public function checkIn(Request $request)
    {
        $request->validate([
            'latitude' => ['required', 'numeric'],
            'longitude' => ['required', 'numeric'],
        ]);

        /** @var \App\Models\Member $member */
        $member = Auth::guard('member')->user();
        $gym = app('currentTenant');
        $settings = Helpers::getSettings();

        $gymLat = data_get($settings, 'general.latitude');
        $gymLng = data_get($settings, 'general.longitude');
        $radius = (float) data_get($settings, 'general.checkin_radius', 100);

        if (blank($gymLat) || blank($gymLng)) {
            return response()->json([
                'success' => false,
                'message' => 'Gym location coordinates are not configured. Please contact staff.'
            ], 422);
        }

        // Calculate distance in meters using Haversine formula
        $distance = $this->calculateDistance(
            (float) $request->latitude, 
            (float) $request->longitude, 
            (float) $gymLat, 
            (float) $gymLng
        );

        if ($distance > $radius) {
            return response()->json([
                'success' => false,
                'message' => sprintf('You are too far from the gym (%d meters away). You must be within %d meters.', round($distance), $radius)
            ], 422);
        }

        $timezone = AppConfig::timezone();
        $today = Carbon::today($timezone);

        $alreadyCheckedIn = $member->attendances()
            ->whereDate('checked_in_at', $today)
            ->exists();

        if ($alreadyCheckedIn) {
            return response()->json([
                'success' => false,
                'message' => 'You have already checked in today!'
            ], 422);
        }

        MemberAttendance::create([
            'gym_id' => $gym->id,
            'member_id' => $member->id,
            'checked_in_at' => now()->timezone($timezone),
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Check-in successful! Welcome to the gym.'
        ]);
    }

    public function initiateSubscription(Request $request)
    {
        $request->validate([
            'plan_id' => ['required', 'integer'],
        ]);

        /** @var \App\Models\Member $member */
        $member = Auth::guard('member')->user();
        $plan = Plan::findOrFail($request->plan_id);

        /** @var RazorpayService $razorpay */
        $razorpay = app(RazorpayService::class);
        $subscription = $razorpay->createSubscription($member, $plan);

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

    public function initiateOrder(Request $request)
    {
        $request->validate([
            'plan_id' => ['required', 'integer'],
        ]);

        /** @var \App\Models\Member $member */
        $member = Auth::guard('member')->user();
        $plan = Plan::findOrFail($request->plan_id);

        /** @var RazorpayService $razorpay */
        $razorpay = app(RazorpayService::class);
        $order = $razorpay->createOrder($member, (float) $plan->amount, 'One-time payment for ' . $plan->name);

        return response()->json([
            'success' => true,
            'data' => $order + [
                'member_name' => $member->name,
                'member_email' => $member->email,
                'member_phone' => $member->contact,
                'description' => 'One-time Plan payment',
            ],
        ]);
    }

    public function verifyPayment(Request $request)
    {
        $request->validate([
            'plan_id' => ['required', 'integer'],
            'razorpay_payment_id' => ['required', 'string'],
            'razorpay_signature' => ['required', 'string'],
            'razorpay_order_id' => ['nullable', 'string'],
            'razorpay_subscription_id' => ['nullable', 'string'],
        ]);

        /** @var \App\Models\Member $member */
        $member = Auth::guard('member')->user();
        $gym = app('currentTenant');
        /** @var RazorpayService $razorpay */
        $razorpay = app(RazorpayService::class);

        // Verify signature
        if ($request->razorpay_subscription_id) {
            $verified = $razorpay->verifyPaymentSignature([
                'razorpay_payment_id' => $request->razorpay_payment_id,
                'razorpay_subscription_id' => $request->razorpay_subscription_id,
                'razorpay_signature' => $request->razorpay_signature,
            ]);
        } else {
            $verified = $razorpay->verifyPaymentSignature([
                'razorpay_order_id' => $request->razorpay_order_id,
                'razorpay_payment_id' => $request->razorpay_payment_id,
                'razorpay_signature' => $request->razorpay_signature,
            ]);
        }

        if (! $verified) {
            return response()->json(['success' => false, 'message' => 'Invalid payment signature.'], 422);
        }

        $plan = Plan::findOrFail($request->plan_id);
        $timezone = AppConfig::timezone();
        $today = Carbon::today($timezone);

        DB::transaction(function() use ($request, $member, $gym, $plan, $today) {
            // 1. Create or update payment transaction
            $transaction = PaymentTransaction::where('razorpay_payment_id', $request->razorpay_payment_id)->first();
            if (! $transaction) {
                PaymentTransaction::create([
                    'gym_id' => $gym->id,
                    'member_id' => $member->id,
                    'razorpay_payment_id' => $request->razorpay_payment_id,
                    'razorpay_order_id' => $request->razorpay_order_id,
                    'razorpay_subscription_id' => $request->razorpay_subscription_id,
                    'amount' => $plan->amount,
                    'status' => 'captured',
                    'currency' => 'INR',
                    'description' => 'Payment from member dashboard for ' . $plan->name,
                    'paid_at' => now(),
                ]);
            } elseif ($transaction->status !== 'captured') {
                $transaction->update([
                    'status' => 'captured',
                    'paid_at' => now(),
                ]);
            }

            // 2. Create or find subscription
            $subscription = Subscription::where('member_id', $member->id)
                ->where('plan_id', $plan->id)
                ->where('status', Status::Ongoing)
                ->latest('end_date')
                ->first();

            if (! $subscription) {
                $latestSubscription = $member->subscriptions()->latest('end_date')->first();
                if ($latestSubscription) {
                    $startDate = $latestSubscription->end_date->isFuture()
                        ? $latestSubscription->end_date->copy()->addDay()->toDateString()
                        : $today->toDateString();
                    
                    if ($latestSubscription->end_date->lt($today)) {
                        $latestSubscription->update(['status' => Status::Renewed]);
                    }
                } else {
                    $startDate = $today->toDateString();
                }

                $endDate = Helpers::calculateSubscriptionEndDate($startDate, $plan->id);

                $subscription = Subscription::create([
                    'gym_id' => $gym->id,
                    'member_id' => $member->id,
                    'plan_id' => $plan->id,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'status' => Status::Ongoing,
                ]);
            }

            // Update member details
            $memberData = [];
            if ($request->razorpay_subscription_id) {
                $memberData['razorpay_subscription_id'] = $request->razorpay_subscription_id;
                $memberData['upi_autopay_active'] = true;
                $memberData['upi_mandate_status'] = 'active';
            }
            if (! empty($memberData)) {
                $member->update($memberData);
            }

            // 3. Create GST Invoice
            $invoice = Invoice::where('subscription_id', $subscription->id)->first();
            if (! $invoice) {
                $invoice = app(GstInvoiceService::class)->createGstInvoice(
                    $member,
                    $plan->amount,
                    'Membership renewal - ' . $plan->name
                );
                
                $invoice->update([
                    'paid_amount' => $plan->amount,
                    'payment_method' => 'online',
                    'status' => Status::Paid,
                ]);
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Payment verified and subscription activated.'
        ]);
    }

    public function downloadInvoice(Invoice $invoice, GstInvoiceService $service)
    {
        /** @var \App\Models\Member $member */
        $member = Auth::guard('member')->user();

        if ($invoice->subscription?->member_id !== $member->id) {
            abort(403, 'Unauthorized');
        }

        if (! $invoice->invoice_pdf_path || ! Storage::disk('local')->exists($invoice->invoice_pdf_path)) {
            $service->generateInvoicePdf($invoice);
            $invoice->refresh();
        }

        $filename = ($invoice->invoice_number ?: $invoice->number ?: 'invoice').'.pdf';

        return Storage::disk('local')->download($invoice->invoice_pdf_path, $filename, [
            'Content-Type' => 'application/pdf',
        ]);
    }

    private function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371000; // in meters

        $latFrom = deg2rad($lat1);
        $lonFrom = deg2rad($lon1);
        $latTo = deg2rad($lat2);
        $lonTo = deg2rad($lon2);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
            cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));
            
        return $angle * $earthRadius;
    }
}
