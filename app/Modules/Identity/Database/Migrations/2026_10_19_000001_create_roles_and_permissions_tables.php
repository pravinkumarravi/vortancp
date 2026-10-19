<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('label');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('label');
            $table->string('module')->default('core');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('role_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['role_id', 'user_id']);
        });

        Schema::create('permission_role', function (Blueprint $table) {
            $table->id();
            $table->foreignId('permission_id')->constrained('permissions')->cascadeOnDelete();
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['permission_id', 'role_id']);
        });

        Schema::create('two_factor_credentials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->text('secret'); // Encrypted
            $table->boolean('is_enabled')->default(false);
            $table->text('recovery_codes')->nullable(); // Encrypted JSON array
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('session_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('ip_address', 45);
            $table->text('user_agent')->nullable();
            $table->timestamp('last_activity_at')->useCurrent();
            $table->boolean('is_revoked')->default(false);
            $table->timestamps();

            $table->index(['user_id', 'is_revoked']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('session_histories');
        Schema::dropIfExists('two_factor_credentials');
        Schema::dropIfExists('permission_role');
        Schema::dropIfExists('role_user');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
    }
};
