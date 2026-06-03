<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table): void {
            $table->string('razorpay_customer_id')->nullable()->after('status');
            $table->string('razorpay_subscription_id')->nullable()->after('razorpay_customer_id');
            $table->boolean('upi_autopay_active')->default(false)->after('razorpay_subscription_id');
            $table->enum('upi_mandate_status', ['pending', 'active', 'paused', 'cancelled', 'halted'])->nullable()->after('upi_autopay_active');
        });
    }

    public function down(): void
    {
        Schema::table('members', function (Blueprint $table): void {
            $table->dropColumn([
                'razorpay_customer_id',
                'razorpay_subscription_id',
                'upi_autopay_active',
                'upi_mandate_status',
            ]);
        });
    }
};
