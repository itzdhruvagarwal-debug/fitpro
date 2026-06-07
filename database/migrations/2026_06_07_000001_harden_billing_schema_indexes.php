<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('invoices') && $this->indexExists('invoices', 'invoices_subscription_id_unique')) {
            Schema::table('invoices', function (Blueprint $table): void {
                $table->dropUnique('invoices_subscription_id_unique');
            });
        }

        if (Schema::hasTable('members')) {
            Schema::table('members', function (Blueprint $table): void {
                $hasGymId = Schema::hasColumn('members', 'gym_id');

                if ($hasGymId && Schema::hasColumn('members', 'code') && ! $this->indexExists('members', 'members_gym_id_code_unique')) {
                    $table->unique(['gym_id', 'code'], 'members_gym_id_code_unique');
                }

                if ($hasGymId && Schema::hasColumn('members', 'razorpay_customer_id') && ! $this->indexExists('members', 'members_gym_razorpay_customer_idx')) {
                    $table->index(['gym_id', 'razorpay_customer_id'], 'members_gym_razorpay_customer_idx');
                }

                if ($hasGymId && Schema::hasColumn('members', 'razorpay_subscription_id') && ! $this->indexExists('members', 'members_gym_razorpay_subscription_idx')) {
                    $table->index(['gym_id', 'razorpay_subscription_id'], 'members_gym_razorpay_subscription_idx');
                }

                if ($hasGymId && Schema::hasColumn('members', 'name') && ! $this->indexExists('members', 'members_gym_name_idx')) {
                    $table->index(['gym_id', 'name'], 'members_gym_name_idx');
                }

                if ($hasGymId && Schema::hasColumn('members', 'contact') && ! $this->indexExists('members', 'members_gym_contact_idx')) {
                    $table->index(['gym_id', 'contact'], 'members_gym_contact_idx');
                }
            });
        }

        if (Schema::hasTable('plans') && ! Schema::hasColumn('plans', 'razorpay_plan_id')) {
            Schema::table('plans', function (Blueprint $table): void {
                $table->string('razorpay_plan_id')->nullable()->after('amount')->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('plans') && Schema::hasColumn('plans', 'razorpay_plan_id')) {
            Schema::table('plans', function (Blueprint $table): void {
                $table->dropColumn('razorpay_plan_id');
            });
        }

        if (Schema::hasTable('members')) {
            Schema::table('members', function (Blueprint $table): void {
                foreach ([
                    'members_gym_contact_idx',
                    'members_gym_name_idx',
                    'members_gym_razorpay_subscription_idx',
                    'members_gym_razorpay_customer_idx',
                    'members_gym_id_code_unique',
                ] as $indexName) {
                    if (! $this->indexExists('members', $indexName)) {
                        continue;
                    }

                    if ($indexName === 'members_gym_id_code_unique') {
                        $table->dropUnique($indexName);

                        continue;
                    }

                    $table->dropIndex($indexName);
                }
            });
        }

        if (Schema::hasTable('invoices') && ! $this->indexExists('invoices', 'invoices_subscription_id_unique')) {
            Schema::table('invoices', function (Blueprint $table): void {
                $table->unique('subscription_id', 'invoices_subscription_id_unique');
            });
        }
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            $indexes = DB::select("PRAGMA index_list('{$table}')");

            foreach ($indexes as $index) {
                if (($index->name ?? null) === $indexName) {
                    return true;
                }
            }

            return false;
        }

        if ($driver === 'mysql') {
            return DB::table('information_schema.statistics')
                ->where('table_schema', DB::getDatabaseName())
                ->where('table_name', $table)
                ->where('index_name', $indexName)
                ->exists();
        }

        if ($driver === 'pgsql') {
            return DB::table('pg_indexes')
                ->where('tablename', $table)
                ->where('indexname', $indexName)
                ->exists();
        }

        return false;
    }
};
