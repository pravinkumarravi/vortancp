<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('firewall_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('server_id')->constrained('servers')->cascadeOnDelete();
            $table->string('direction')->default('inbound'); // inbound, outbound
            $table->string('action')->default('allow'); // allow, deny, reject, limit
            $table->string('protocol')->default('tcp'); // tcp, udp, any
            $table->string('port')->default('80'); // 80, 443, 20000:20050, any
            $table->string('source_ip')->default('0.0.0.0/0');
            $table->string('comment')->nullable();
            $table->integer('priority')->default(100);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['server_id', 'direction', 'is_active']);
        });

        Schema::create('banned_ips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('server_id')->constrained('servers')->cascadeOnDelete();
            $table->string('ip_address');
            $table->string('jail')->default('sshd');
            $table->unsignedInteger('failures_count')->default(5);
            $table->string('reason')->nullable();
            $table->timestamp('banned_at');
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['server_id', 'ip_address']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('banned_ips');
        Schema::dropIfExists('firewall_rules');
    }
};
