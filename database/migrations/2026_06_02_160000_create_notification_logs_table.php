<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('member_id')->nullable()->constrained('members')->nullOnDelete();
            $table->enum('channel', ['whatsapp', 'sms', 'email']);
            $table->string('template_name');
            $table->string('phone')->nullable();
            $table->text('message_preview')->nullable();
            $table->json('msg91_response')->nullable();
            $table->enum('status', ['sent', 'failed', 'pending'])->default('pending');
            $table->timestamp('sent_at')->nullable();
            $table->string('error_message')->nullable();
            $table->timestamps();

            $table->index(['channel', 'status']);
            $table->index(['template_name']);
            $table->index(['sent_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_logs');
    }
};
