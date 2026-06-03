<?php

use App\Models\Member;
use App\Models\PaymentTransaction;
use App\Models\Plan;
use App\Models\Subscription;
use App\Services\RazorpayService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery as MockeryFacade;

uses(RefreshDatabase::class);

it('rejects webhook processing when signature verification fails', function (): void {
    $mock = MockeryFacade::mock(RazorpayService::class);
    $mock->shouldReceive('verifyWebhookSignature')->once()->andReturnFalse();
    app()->instance(RazorpayService::class, $mock);

    $response = $this->postJson(route('razorpay.webhook'), [
        'event' => 'payment.captured',
        'payload' => [],
    ]);

    $response->assertOk()->assertJson(['ok' => true]);
});

it('processes payment captured webhook and extends member subscription', function (): void {
    $member = Member::factory()->create([
        'razorpay_customer_id' => 'cust_123',
    ]);
    $plan = Plan::factory()->create([
        'days' => 30,
    ]);
    $subscription = Subscription::factory()->create([
        'member_id' => $member->id,
        'plan_id' => $plan->id,
        'end_date' => now()->toDateString(),
    ]);

    $mock = MockeryFacade::mock(RazorpayService::class);
    $mock->shouldReceive('verifyWebhookSignature')->once()->andReturnTrue();
    app()->instance(RazorpayService::class, $mock);

    $this->withHeader('X-Razorpay-Signature', 'sig')->postJson(route('razorpay.webhook'), [
        'event' => 'payment.captured',
        'payload' => [
            'payment' => [
                'entity' => [
                    'id' => 'pay_1',
                    'amount' => 100000,
                    'currency' => 'INR',
                    'method' => 'upi',
                    'customer_id' => 'cust_123',
                    'status' => 'captured',
                ],
            ],
        ],
    ])->assertOk();

    expect(PaymentTransaction::query()->where('razorpay_payment_id', 'pay_1')->exists())->toBeTrue();

    $updated = $subscription->fresh();
    expect($updated?->end_date?->gt(now()))->toBeTrue();
});

it('marks mandate active when subscription gets activated', function (): void {
    $member = Member::factory()->create([
        'razorpay_subscription_id' => 'sub_1',
        'upi_autopay_active' => false,
        'upi_mandate_status' => 'pending',
    ]);

    $mock = MockeryFacade::mock(RazorpayService::class);
    $mock->shouldReceive('verifyWebhookSignature')->once()->andReturnTrue();
    app()->instance(RazorpayService::class, $mock);

    $this->withHeader('X-Razorpay-Signature', 'sig')->postJson(route('razorpay.webhook'), [
        'event' => 'subscription.activated',
        'payload' => [
            'subscription' => [
                'entity' => [
                    'id' => 'sub_1',
                ],
            ],
        ],
    ])->assertOk();

    $member->refresh();
    expect($member->upi_autopay_active)->toBeTrue();
    expect($member->upi_mandate_status)->toBe('active');
});

it('marks transaction failed on payment failed webhook', function (): void {
    $member = Member::factory()->create([
        'razorpay_customer_id' => 'cust_2',
    ]);

    $mock = MockeryFacade::mock(RazorpayService::class);
    $mock->shouldReceive('verifyWebhookSignature')->once()->andReturnTrue();
    app()->instance(RazorpayService::class, $mock);

    $this->withHeader('X-Razorpay-Signature', 'sig')->postJson(route('razorpay.webhook'), [
        'event' => 'payment.failed',
        'payload' => [
            'payment' => [
                'entity' => [
                    'id' => 'pay_fail_1',
                    'amount' => 50000,
                    'currency' => 'INR',
                    'method' => 'upi',
                    'customer_id' => 'cust_2',
                    'status' => 'failed',
                ],
            ],
        ],
    ])->assertOk();

    $transaction = PaymentTransaction::query()
        ->where('razorpay_payment_id', 'pay_fail_1')
        ->first();

    expect($transaction)->not->toBeNull();
    expect($transaction?->status)->toBe('failed');
    expect($transaction?->member_id)->toBe($member->id);
});
