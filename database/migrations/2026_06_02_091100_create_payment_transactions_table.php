<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_transactions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
            $table->string('razorpay_payment_id')->nullable()->unique();
            $table->string('razorpay_order_id')->nullable()->index();
            $table->string('razorpay_subscription_id')->nullable()->index();
            $table->decimal('amount', 10, 2);
            $table->string('currency', 8)->default('INR');
            $table->enum('status', ['created', 'captured', 'failed', 'refunded'])->default('created')->index();
            $table->string('payment_method')->nullable();
            $table->string('description')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_transactions');
    }
};
