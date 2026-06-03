<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tableNames = config('permission.table_names');
        $teamKey = config('permission.column_names.team_foreign_key', 'gym_id');

        if (! is_array($tableNames) || empty($tableNames)) {
            return;
        }

        $roles = $tableNames['roles'] ?? 'roles';
        $modelHasRoles = $tableNames['model_has_roles'] ?? 'model_has_roles';
        $modelHasPermissions = $tableNames['model_has_permissions'] ?? 'model_has_permissions';

        if (Schema::hasTable($roles) && ! Schema::hasColumn($roles, $teamKey)) {
            Schema::table($roles, function (Blueprint $table) use ($teamKey): void {
                $table->unsignedBigInteger($teamKey)->nullable()->index();
            });
        }

        if (Schema::hasTable($modelHasRoles) && ! Schema::hasColumn($modelHasRoles, $teamKey)) {
            Schema::table($modelHasRoles, function (Blueprint $table) use ($teamKey): void {
                $table->unsignedBigInteger($teamKey)->nullable()->index();
            });
        }

        if (Schema::hasTable($modelHasPermissions) && ! Schema::hasColumn($modelHasPermissions, $teamKey)) {
            Schema::table($modelHasPermissions, function (Blueprint $table) use ($teamKey): void {
                $table->unsignedBigInteger($teamKey)->nullable()->index();
            });
        }

        // Rebuild pivot primary keys to include team key for proper per-gym role/permission assignments.
        if (Schema::hasTable($modelHasRoles)) {
            try {
                DB::statement("ALTER TABLE `{$modelHasRoles}` DROP PRIMARY KEY");
            } catch (Throwable) {
                // noop
            }
            try {
                DB::statement("ALTER TABLE `{$modelHasRoles}` ADD PRIMARY KEY (`{$teamKey}`, `role_id`, `model_id`, `model_type`)");
            } catch (Throwable) {
                // noop
            }
        }

        if (Schema::hasTable($modelHasPermissions)) {
            try {
                DB::statement("ALTER TABLE `{$modelHasPermissions}` DROP PRIMARY KEY");
            } catch (Throwable) {
                // noop
            }
            try {
                DB::statement("ALTER TABLE `{$modelHasPermissions}` ADD PRIMARY KEY (`{$teamKey}`, `permission_id`, `model_id`, `model_type`)");
            } catch (Throwable) {
                // noop
            }
        }
    }

    public function down(): void
    {
        $tableNames = config('permission.table_names');
        $teamKey = config('permission.column_names.team_foreign_key', 'gym_id');

        if (! is_array($tableNames) || empty($tableNames)) {
            return;
        }

        foreach ([
            $tableNames['roles'] ?? 'roles',
            $tableNames['model_has_roles'] ?? 'model_has_roles',
            $tableNames['model_has_permissions'] ?? 'model_has_permissions',
        ] as $tableName) {
            if (! Schema::hasTable($tableName) || ! Schema::hasColumn($tableName, $teamKey)) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($teamKey): void {
                try {
                    $table->dropColumn($teamKey);
                } catch (Throwable) {
                    // noop
                }
            });
        }

        $modelHasRoles = $tableNames['model_has_roles'] ?? 'model_has_roles';
        $modelHasPermissions = $tableNames['model_has_permissions'] ?? 'model_has_permissions';

        if (Schema::hasTable($modelHasRoles)) {
            try {
                DB::statement("ALTER TABLE `{$modelHasRoles}` DROP PRIMARY KEY");
            } catch (Throwable) {
                // noop
            }
            try {
                DB::statement("ALTER TABLE `{$modelHasRoles}` ADD PRIMARY KEY (`role_id`, `model_id`, `model_type`)");
            } catch (Throwable) {
                // noop
            }
        }

        if (Schema::hasTable($modelHasPermissions)) {
            try {
                DB::statement("ALTER TABLE `{$modelHasPermissions}` DROP PRIMARY KEY");
            } catch (Throwable) {
                // noop
            }
            try {
                DB::statement("ALTER TABLE `{$modelHasPermissions}` ADD PRIMARY KEY (`permission_id`, `model_id`, `model_type`)");
            } catch (Throwable) {
                // noop
            }
        }
    }
};
