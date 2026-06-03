<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table): void {
            if (! Schema::hasColumn('members', 'whatsapp_enabled')) {
                $table->boolean('whatsapp_enabled')->default(true)->after('contact');
            }
            if (! Schema::hasColumn('members', 'sms_enabled')) {
                $table->boolean('sms_enabled')->default(true)->after('whatsapp_enabled');
            }
            if (! Schema::hasColumn('members', 'notification_phone')) {
                $table->string('notification_phone')->nullable()->after('sms_enabled');
            }
        });
    }

    public function down(): void
    {
        Schema::table('members', function (Blueprint $table): void {
            $columns = ['whatsapp_enabled', 'sms_enabled', 'notification_phone'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('members', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
