<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('domain_traffic_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('domain_id')->constrained('domains')->cascadeOnDelete();
            $table->foreignId('hosting_account_id')->constrained('hosting_accounts')->cascadeOnDelete();
            $table->date('date')->index();
            $table->unsignedInteger('total_requests')->default(0);
            $table->unsignedInteger('unique_visitors')->default(0);
            $table->unsignedBigInteger('bandwidth_bytes')->default(0);
            $table->unsignedInteger('status_2xx')->default(0);
            $table->unsignedInteger('status_3xx')->default(0);
            $table->unsignedInteger('status_4xx')->default(0);
            $table->unsignedInteger('status_5xx')->default(0);
            $table->json('top_paths')->nullable();
            $table->json('top_referrers')->nullable();
            $table->json('top_user_agents')->nullable();
            $table->timestamps();

            $table->unique(['domain_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('domain_traffic_metrics');
    }
};
