<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('server_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('server_id')->constrained('servers')->cascadeOnDelete();
            $table->float('cpu_pct', 5, 2);
            $table->float('memory_pct', 5, 2);
            $table->unsignedBigInteger('memory_used_mb');
            $table->unsignedBigInteger('memory_total_mb');
            $table->float('disk_pct', 5, 2);
            $table->unsignedBigInteger('disk_used_mb');
            $table->unsignedBigInteger('disk_total_mb');
            $table->float('load_1min', 6, 2)->default(0.00);
            $table->timestamp('recorded_at');
            $table->timestamps();

            $table->index(['server_id', 'recorded_at']);
        });

        Schema::create('account_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hosting_account_id')->constrained('hosting_accounts')->cascadeOnDelete();
            $table->unsignedBigInteger('disk_used_mb')->default(0);
            $table->unsignedBigInteger('bandwidth_used_mb')->default(0);
            $table->unsignedBigInteger('inodes_used')->default(0);
            $table->timestamp('recorded_at');
            $table->timestamps();

            $table->index(['hosting_account_id', 'recorded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_metrics');
        Schema::dropIfExists('server_metrics');
    }
};
