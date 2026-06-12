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
        Schema::create('member_attendances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('gym_id')->index();
            $table->unsignedBigInteger('member_id')->index();
            $table->dateTime('checked_in_at')->index();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->timestamps();

            $table->foreign('gym_id')
                ->references('id')
                ->on('gyms')
                ->cascadeOnDelete();

            $table->foreign('member_id')
                ->references('id')
                ->on('members')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('member_attendances');
    }
};
