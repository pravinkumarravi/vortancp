<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('servers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('hostname')->unique();
            $table->string('ip_address', 45);
            $table->unsignedSmallInteger('port')->default(8443);
            $table->string('connection_type')->default('local_socket'); // 'local_socket' or 'remote_mtls'
            $table->text('auth_token')->nullable(); // Encrypted secret
            $table->string('status')->default('active'); // active, maintenance, offline
            $table->string('os_name')->default('Linux');
            $table->string('os_version')->nullable();
            $table->string('kernel_version')->nullable();
            $table->unsignedSmallInteger('cpu_cores')->default(4);
            $table->unsignedInteger('total_ram_mb')->default(8192);
            $table->unsignedInteger('used_ram_mb')->default(0);
            $table->unsignedBigInteger('total_disk_gb')->default(160);
            $table->unsignedBigInteger('used_disk_gb')->default(0);
            $table->float('load_average')->default(0.0);
            $table->timestamp('last_heartbeat_at')->nullable();
            $table->timestamps();

            $table->index('status');
        });

        Schema::create('server_capabilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('server_id')->constrained('servers')->cascadeOnDelete();
            $table->string('capability'); // e.g. nginx, apache, php-8.3, mariadb, postfix, dovecot, powerdns
            $table->string('version')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['server_id', 'capability']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('server_capabilities');
        Schema::dropIfExists('servers');
    }
};
