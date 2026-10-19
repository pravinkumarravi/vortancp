<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ftp_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hosting_account_id')->constrained('hosting_accounts')->cascadeOnDelete();
            $table->string('username')->unique();
            $table->string('password_hash');
            $table->string('homedir');
            $table->unsignedInteger('quota_mb')->nullable();
            $table->unsignedInteger('upload_bandwidth_kb')->nullable();
            $table->unsignedInteger('download_bandwidth_kb')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['hosting_account_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ftp_accounts');
    }
};
