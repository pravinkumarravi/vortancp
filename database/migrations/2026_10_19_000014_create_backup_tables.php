<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('backups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hosting_account_id')->constrained('hosting_accounts')->cascadeOnDelete();
            $table->string('filename');
            $table->string('type')->default('full'); // full, files_only, database_only, email_only
            $table->string('destination')->default('local'); // local, s3, remote_ftp
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->string('status')->default('pending'); // pending, in_progress, completed, failed
            $table->string('checksum_sha256')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['hosting_account_id', 'status']);
        });

        Schema::create('backup_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hosting_account_id')->constrained('hosting_accounts')->cascadeOnDelete();
            $table->string('frequency')->default('daily'); // daily, weekly, monthly
            $table->unsignedInteger('retention_count')->default(7);
            $table->string('destination')->default('local');
            $table->boolean('include_files')->default(true);
            $table->boolean('include_databases')->default(true);
            $table->boolean('include_emails')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_run_at')->nullable();
            $table->timestamps();

            $table->index(['hosting_account_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('backup_schedules');
        Schema::dropIfExists('backups');
    }
};
