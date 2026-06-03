<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('settings')) {
            return;
        }

        Schema::table('settings', function (Blueprint $table): void {
            if (! Schema::hasColumn('settings', 'whatsapp_enabled')) {
                $table->boolean('whatsapp_enabled')->default(false);
            }
            if (! Schema::hasColumn('settings', 'sms_enabled')) {
                $table->boolean('sms_enabled')->default(false);
            }
            if (! Schema::hasColumn('settings', 'renewal_reminder_days')) {
                $table->json('renewal_reminder_days')->nullable();
            }
            if (! Schema::hasColumn('settings', 'send_welcome_message')) {
                $table->boolean('send_welcome_message')->default(true);
            }
            if (! Schema::hasColumn('settings', 'send_payment_confirmation')) {
                $table->boolean('send_payment_confirmation')->default(true);
            }
            if (! Schema::hasColumn('settings', 'send_expiry_warning')) {
                $table->boolean('send_expiry_warning')->default(true);
            }
            if (! Schema::hasColumn('settings', 'quiet_hours_start')) {
                $table->time('quiet_hours_start')->default('22:00');
            }
            if (! Schema::hasColumn('settings', 'quiet_hours_end')) {
                $table->time('quiet_hours_end')->default('08:00');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('settings')) {
            return;
        }

        Schema::table('settings', function (Blueprint $table): void {
            $columns = [
                'whatsapp_enabled',
                'sms_enabled',
                'renewal_reminder_days',
                'send_welcome_message',
                'send_payment_confirmation',
                'send_expiry_warning',
                'quiet_hours_start',
                'quiet_hours_end',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('settings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
