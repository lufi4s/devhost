<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('roles', 'guard_name')) {
            Schema::table('roles', function (Blueprint $table) {
                $table->string('guard_name')->default('web')->after('name');
            });
        }

        if (! Schema::hasColumn('role_has_permissions', 'guard_name')) {
            Schema::table('role_has_permissions', function (Blueprint $table) {
                $table->string('guard_name')->default('web')->after('permission_id');
            });
        }

        if (! Schema::hasColumn('model_has_permissions', 'guard_name')) {
            Schema::table('model_has_permissions', function (Blueprint $table) {
                $table->string('guard_name')->default('web')->after('model_type');
            });
        }

        if (! Schema::hasTable('model_has_roles')) {
            Schema::create('model_has_roles', function (Blueprint $table) {
                $table->morphs('model');
                $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
                $table->string('guard_name')->default('web');
                $table->primary(['model_id', 'model_type', 'role_id']);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('roles', 'guard_name')) {
            Schema::table('roles', function (Blueprint $table) {
                $table->dropColumn('guard_name');
            });
        }
    }
};
