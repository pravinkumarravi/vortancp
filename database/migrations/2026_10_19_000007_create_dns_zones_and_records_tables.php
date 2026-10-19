<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dns_zones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('domain_id')->nullable()->constrained('domains')->nullOnDelete();
            $table->foreignId('hosting_account_id')->constrained('hosting_accounts')->cascadeOnDelete();
            $table->string('name')->unique();
            $table->string('status')->default('active'); // active, suspended
            $table->unsignedBigInteger('serial')->default(2026101901);
            $table->string('primary_ns')->default('ns1.vortan.local');
            $table->string('admin_email')->default('hostmaster.vortan.local');
            $table->unsignedInteger('refresh_sec')->default(10800);
            $table->unsignedInteger('retry_sec')->default(3600);
            $table->unsignedInteger('expire_sec')->default(604800);
            $table->unsignedInteger('minimum_ttl')->default(3600);
            $table->timestamps();

            $table->index(['hosting_account_id', 'status']);
        });

        Schema::create('dns_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dns_zone_id')->constrained('dns_zones')->cascadeOnDelete();
            $table->string('name')->default('@'); // @, www, mail, ftp, etc.
            $table->string('type', 10)->default('A'); // A, AAAA, CNAME, MX, TXT, SRV, CAA, NS
            $table->text('content');
            $table->unsignedInteger('ttl')->default(3600);
            $table->unsignedSmallInteger('priority')->nullable(); // for MX, SRV
            $table->unsignedSmallInteger('weight')->nullable();   // for SRV
            $table->unsignedSmallInteger('port')->nullable();     // for SRV
            $table->timestamps();

            $table->index(['dns_zone_id', 'type']);
            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dns_records');
        Schema::dropIfExists('dns_zones');
    }
};
