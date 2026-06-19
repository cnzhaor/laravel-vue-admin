<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->string('name');
            $table->string('code')->unique();
            $table->unsignedInteger('sort')->default(0);
            $table->boolean('enabled')->default(true);
            $table->timestamps();
        });

        Schema::create('positions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->unsignedInteger('sort')->default(0);
            $table->boolean('enabled')->default(true);
            $table->text('remark')->nullable();
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->unique()->after('id');
            $table->string('phone')->nullable()->after('email');
            $table->foreignId('department_id')->nullable()->after('phone')->constrained()->nullOnDelete();
            $table->foreignId('position_id')->nullable()->after('department_id')->constrained()->nullOnDelete();
            $table->boolean('enabled')->default(true);
            $table->boolean('is_super_admin')->default(false);
            $table->timestamp('last_login_at')->nullable();
        });

        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->boolean('enabled')->default(true);
            $table->boolean('is_system')->default(false);
            $table->text('remark')->nullable();
            $table->timestamps();
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('type')->default('api');
            $table->text('remark')->nullable();
            $table->timestamps();
        });

        Schema::create('menus', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('menus')->nullOnDelete();
            $table->string('name');
            $table->string('path')->nullable();
            $table->string('component')->nullable();
            $table->string('icon')->nullable();
            $table->string('permission_code')->nullable();
            $table->string('type')->default('menu');
            $table->unsignedInteger('sort')->default(0);
            $table->boolean('visible')->default(true);
            $table->boolean('enabled')->default(true);
            $table->timestamps();
        });

        Schema::create('role_user', function (Blueprint $table) {
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->primary(['role_id', 'user_id']);
        });

        Schema::create('permission_role', function (Blueprint $table) {
            $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->primary(['permission_id', 'role_id']);
        });

        Schema::create('dictionaries', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->boolean('enabled')->default(true);
            $table->text('remark')->nullable();
            $table->timestamps();
        });

        Schema::create('dictionary_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dictionary_id')->constrained()->cascadeOnDelete();
            $table->string('label');
            $table->string('value');
            $table->unsignedInteger('sort')->default(0);
            $table->boolean('enabled')->default(true);
            $table->string('tag_type')->nullable();
            $table->timestamps();
            $table->unique(['dictionary_id', 'value']);
        });

        Schema::create('system_parameters', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->boolean('is_public')->default(false);
            $table->text('remark')->nullable();
            $table->timestamps();
        });

        Schema::create('operation_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('method', 10);
            $table->string('path');
            $table->string('ip', 45)->nullable();
            $table->unsignedSmallInteger('status');
            $table->unsignedInteger('duration_ms')->default(0);
            $table->json('payload')->nullable();
            $table->timestamps();
        });

        Schema::create('login_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('username');
            $table->boolean('success');
            $table->string('ip', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('message')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('login_logs');
        Schema::dropIfExists('operation_logs');
        Schema::dropIfExists('system_parameters');
        Schema::dropIfExists('dictionary_items');
        Schema::dropIfExists('dictionaries');
        Schema::dropIfExists('permission_role');
        Schema::dropIfExists('role_user');
        Schema::dropIfExists('menus');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('department_id');
            $table->dropConstrainedForeignId('position_id');
            $table->dropColumn(['username', 'phone', 'enabled', 'is_super_admin', 'last_login_at']);
        });
        Schema::dropIfExists('positions');
        Schema::dropIfExists('departments');
    }
};

