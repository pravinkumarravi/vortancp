<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cron_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hosting_account_id')->constrained('hosting_accounts')->cascadeOnDelete();
            $table->string('minute')->default('*');
            $table->string('hour')->default('*');
            $table->string('day_of_month')->default('*');
            $table->string('month')->default('*');
            $table->string('day_of_week')->default('*');
            $table->text('command');
            $table->string('description')->nullable();
            $table->string('email_notification')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_run_at')->nullable();
            $table->timestamps();

            $table->index(['hosting_account_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cron_jobs');
    }
};
