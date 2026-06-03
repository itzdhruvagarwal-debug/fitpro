<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tables = [
            'users',
            'members',
            'plans',
            'services',
            'subscriptions',
            'invoices',
            'invoice_transactions',
            'expenses',
            'enquiries',
            'follow_ups',
            'payment_transactions',
            'notification_logs',
        ];

        foreach ($tables as $tableName) {
            if (! Schema::hasTable($tableName)) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($tableName): void {
                if (! Schema::hasColumn($tableName, 'gym_id')) {
                    $table->unsignedBigInteger('gym_id')->nullable()->index();
                }
            });

            // Foreign key added in separate step (MySQL requires index first in some setups)
            Schema::table($tableName, function (Blueprint $table) use ($tableName): void {
                $fkName = "{$tableName}_gym_id_fk";

                // Avoid duplicate foreign key creation in repeated migrations.
                // Laravel doesn't provide a simple schema helper; we rely on try/catch.
                try {
                    $table->foreign('gym_id', $fkName)->references('id')->on('gyms')->onDelete('cascade');
                } catch (Throwable) {
                    // ignore
                }

                try {
                    $table->index(['gym_id', 'id'], "{$tableName}_gym_id_id_idx");
                } catch (Throwable) {
                    // ignore
                }
            });
        }
    }

    public function down(): void
    {
        $tables = [
            'users',
            'members',
            'plans',
            'services',
            'subscriptions',
            'invoices',
            'invoice_transactions',
            'expenses',
            'enquiries',
            'follow_ups',
            'payment_transactions',
            'notification_logs',
        ];

        foreach ($tables as $tableName) {
            if (! Schema::hasTable($tableName) || ! Schema::hasColumn($tableName, 'gym_id')) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($tableName): void {
                try {
                    $table->dropForeign("{$tableName}_gym_id_fk");
                } catch (Throwable) {
                    // ignore
                }

                try {
                    $table->dropIndex("{$tableName}_gym_id_id_idx");
                } catch (Throwable) {
                    // ignore
                }

                try {
                    $table->dropColumn('gym_id');
                } catch (Throwable) {
                    // ignore
                }
            });
        }
    }
};
