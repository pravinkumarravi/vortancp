<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monitored_services', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('display_name');
            $table->string('category')->default('system'); // web, database, dns, mail, ftp, security, php, system
            $table->string('description')->nullable();
            $table->boolean('is_monitored')->default(true);
            $table->boolean('auto_restart')->default(true);
            $table->string('status')->default('running'); // running, stopped, failed, unknown
            $table->timestamp('last_checked_at')->nullable();
            $table->timestamp('last_restarted_at')->nullable();
            $table->unsignedInteger('restart_count')->default(0);
            $table->timestamps();

            $table->index(['category', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monitored_services');
    }
};
