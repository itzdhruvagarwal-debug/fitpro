<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('gyms', function (Blueprint $table) {
            $table->string('razorpay_subscription_id')->nullable()->index();
            $table->string('razorpay_mandate_status')->nullable();
        });

        Schema::create('gym_subscription_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('gym_id')->index();
            $table->string('razorpay_payment_id')->nullable()->index();
            $table->string('razorpay_order_id')->nullable()->index();
            $table->string('razorpay_subscription_id')->nullable()->index();
            $table->decimal('amount', 10, 2);
            $table->string('plan');
            $table->string('status');
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->foreign('gym_id')
                ->references('id')
                ->on('gyms')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gym_subscription_payments');

        Schema::table('gyms', function (Blueprint $table) {
            $table->dropColumn(['razorpay_subscription_id', 'razorpay_mandate_status']);
        });
    }
};
