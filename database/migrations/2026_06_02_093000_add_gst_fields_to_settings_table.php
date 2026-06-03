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
            if (! Schema::hasColumn('settings', 'gym_gstin')) {
                $table->string('gym_gstin', 15)->nullable();
            }
            if (! Schema::hasColumn('settings', 'gym_pan')) {
                $table->string('gym_pan', 10)->nullable();
            }
            if (! Schema::hasColumn('settings', 'gym_legal_name')) {
                $table->string('gym_legal_name')->nullable();
            }
            if (! Schema::hasColumn('settings', 'gym_address_line1')) {
                $table->string('gym_address_line1')->nullable();
            }
            if (! Schema::hasColumn('settings', 'gym_address_line2')) {
                $table->string('gym_address_line2')->nullable();
            }
            if (! Schema::hasColumn('settings', 'gym_city')) {
                $table->string('gym_city')->nullable();
            }
            if (! Schema::hasColumn('settings', 'gym_state')) {
                $table->string('gym_state')->nullable();
            }
            if (! Schema::hasColumn('settings', 'gym_pincode')) {
                $table->string('gym_pincode', 6)->nullable();
            }
            if (! Schema::hasColumn('settings', 'gym_state_code')) {
                $table->string('gym_state_code', 2)->nullable();
            }
            if (! Schema::hasColumn('settings', 'invoice_prefix')) {
                $table->string('invoice_prefix')->default('INV');
            }
            if (! Schema::hasColumn('settings', 'invoice_counter')) {
                $table->unsignedBigInteger('invoice_counter')->default(1);
            }
            if (! Schema::hasColumn('settings', 'gst_enabled')) {
                $table->boolean('gst_enabled')->default(false);
            }
            if (! Schema::hasColumn('settings', 'gst_rate')) {
                $table->decimal('gst_rate', 5, 2)->default(18.00);
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
                'gym_gstin',
                'gym_pan',
                'gym_legal_name',
                'gym_address_line1',
                'gym_address_line2',
                'gym_city',
                'gym_state',
                'gym_pincode',
                'gym_state_code',
                'invoice_prefix',
                'invoice_counter',
                'gst_enabled',
                'gst_rate',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('settings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
