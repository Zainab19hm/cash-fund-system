<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1) users
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('username', 100)->unique();
            $table->string('password');
            $table->enum('role', ['admin', 'investor', 'client']);
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_login_at')->nullable();
            $table->timestamps();
        });

        // 2) permissions
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('label');
            $table->timestamps();
        });

        // 3) role_permissions (يعتمد على permissions)
        Schema::create('role_permissions', function (Blueprint $table) {
            $table->id();
            $table->enum('role', ['admin', 'investor', 'client']);
            $table->foreignId('permission_id')
                  ->constrained('permissions')
                  ->onDelete('cascade');
            $table->timestamps();
            $table->unique(['role', 'permission_id']);
        });

        // 4) log_audit (يعتمد على users)
        Schema::create('log_audit', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                  ->constrained('users')
                  ->onDelete('cascade');
            $table->string('action', 50);
            $table->string('entity_type', 50);
            $table->unsignedBigInteger('entity_id');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        // الترتيب العكسي لتفادي مشاكل الـ Foreign Keys
        Schema::dropIfExists('log_audit');
        Schema::dropIfExists('role_permissions');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('users');
    }
};