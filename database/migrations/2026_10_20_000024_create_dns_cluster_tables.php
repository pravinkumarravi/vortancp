<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dns_cluster_nodes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('hostname')->unique();
            $table->string('ip_address');
            $table->string('api_key_hash');
            $table->string('sync_type')->default('slave');
            $table->string('status')->default('active');
            $table->unsignedInteger('zones_count')->default(0);
            $table->float('latency_ms')->default(0);
            $table->timestamp('last_ping_at')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
        });

        Schema::create('dns_sync_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dns_cluster_node_id')->constrained('dns_cluster_nodes')->cascadeOnDelete();
            $table->foreignId('dns_zone_id')->constrained('dns_zones')->cascadeOnDelete();
            $table->string('event')->default('zone_push');
            $table->string('status')->default('success');
            $table->unsignedInteger('records_count')->default(0);
            $table->text('details')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dns_sync_logs');
        Schema::dropIfExists('dns_cluster_nodes');
    }
};
