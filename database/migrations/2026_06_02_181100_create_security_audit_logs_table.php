<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('security_audit_logs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('gym_id')->nullable()->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('event_type', 100)->index();
            $table->string('email')->nullable()->index();
            $table->ipAddress('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->string('outcome', 30)->index();
            $table->json('context')->nullable();
            $table->timestamps();

            $table->foreign('gym_id', 'security_audit_logs_gym_id_fk')
                ->references('id')
                ->on('gyms')
                ->nullOnDelete();
            $table->foreign('user_id', 'security_audit_logs_user_id_fk')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('security_audit_logs', function (Blueprint $table): void {
            try {
                $table->dropForeign('security_audit_logs_gym_id_fk');
            } catch (Throwable) {
                // ignore
            }

            try {
                $table->dropForeign('security_audit_logs_user_id_fk');
            } catch (Throwable) {
                // ignore
            }
        });

        Schema::dropIfExists('security_audit_logs');
    }
};
