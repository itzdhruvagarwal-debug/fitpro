<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gyms', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('domain')->nullable();

            $table->string('owner_name')->nullable();
            $table->string('owner_email')->nullable();
            $table->string('owner_phone')->nullable();

            $table->string('gstin', 15)->nullable();
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('pincode')->nullable();

            $table->string('logo_path')->nullable();

            $table->string('plan')->nullable();
            $table->timestamp('plan_expires_at')->nullable();
            $table->string('status')->default('trial'); // active/suspended/trial
            $table->json('settings')->nullable();
            $table->timestamp('trial_ends_at')->nullable();

            // Spatie tenant model expects a database name in some setups; keep for compatibility.
            $table->string('database')->nullable()->default('default');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gyms');
    }
};
