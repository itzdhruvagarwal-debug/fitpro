<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('razorpay_webhook_events', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('gym_id')->nullable()->index();
            $table->string('event_name')->index();
            $table->string('razorpay_event_id')->nullable()->index();
            $table->string('razorpay_payment_id')->nullable()->index();
            $table->string('razorpay_order_id')->nullable()->index();
            $table->string('razorpay_subscription_id')->nullable()->index();
            $table->string('signature_hash', 64)->nullable();
            $table->string('payload_hash', 64)->unique();
            $table->timestamp('processed_at')->nullable()->index();
            $table->timestamps();

            $table->foreign('gym_id', 'razorpay_webhook_events_gym_id_fk')
                ->references('id')
                ->on('gyms')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('razorpay_webhook_events', function (Blueprint $table): void {
            try {
                $table->dropForeign('razorpay_webhook_events_gym_id_fk');
            } catch (Throwable) {
                // ignore
            }
        });

        Schema::dropIfExists('razorpay_webhook_events');
    }
};
