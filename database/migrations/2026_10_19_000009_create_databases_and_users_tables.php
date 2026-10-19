<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('databases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hosting_account_id')->constrained('hosting_accounts')->cascadeOnDelete();
            $table->foreignId('server_id')->constrained('servers')->cascadeOnDelete();
            $table->string('name')->unique();
            $table->string('db_engine')->default('mysql'); // mysql, mariadb, postgresql
            $table->string('charset')->default('utf8mb4');
            $table->string('collation')->default('utf8mb4_unicode_ci');
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->string('status')->default('active'); // active, suspended
            $table->timestamps();

            $table->index(['hosting_account_id', 'status']);
        });

        Schema::create('database_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hosting_account_id')->constrained('hosting_accounts')->cascadeOnDelete();
            $table->foreignId('server_id')->constrained('servers')->cascadeOnDelete();
            $table->string('username')->unique();
            $table->string('host')->default('localhost'); // localhost or % or IP
            $table->string('password_hash');
            $table->string('status')->default('active');
            $table->timestamps();

            $table->index(['hosting_account_id', 'username']);
        });

        Schema::create('database_user_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('database_id')->constrained('databases')->cascadeOnDelete();
            $table->foreignId('database_user_id')->constrained('database_users')->cascadeOnDelete();
            $table->json('privileges'); // ['ALL'] or ['SELECT', 'INSERT', 'UPDATE', 'DELETE', etc.]
            $table->timestamps();

            $table->unique(['database_id', 'database_user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('database_user_assignments');
        Schema::dropIfExists('database_users');
        Schema::dropIfExists('databases');
    }
};
