<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('migration_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('source_type')->default('cpanel');
            $table->string('archive_name');
            $table->string('archive_path');
            $table->unsignedBigInteger('archive_size_bytes')->default(0);
            $table->string('target_username');
            $table->string('target_domain');
            $table->string('status')->default('pending');
            $table->unsignedTinyInteger('step_progress')->default(0);
            $table->string('current_step')->default('Queued');
            $table->json('log_messages')->nullable();
            $table->json('migrated_assets')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('migration_jobs');
    }
};
