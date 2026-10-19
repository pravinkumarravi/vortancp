<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('waf_configurations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('server_id')->constrained('servers')->cascadeOnDelete();
            $table->foreignId('domain_id')->nullable()->constrained('domains')->cascadeOnDelete();
            $table->boolean('is_enabled')->default(true);
            $table->unsignedTinyInteger('paranoia_level')->default(1);
            $table->boolean('block_sqli')->default(true);
            $table->boolean('block_xss')->default(true);
            $table->boolean('block_rfi')->default(true);
            $table->boolean('block_code_injection')->default(true);
            $table->unsignedInteger('anomaly_threshold')->default(5);
            $table->text('custom_rules')->nullable();
            $table->json('disabled_rule_ids')->nullable();
            $table->timestamps();

            $table->unique(['server_id', 'domain_id']);
        });

        Schema::create('security_scans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('server_id')->constrained('servers')->cascadeOnDelete();
            $table->foreignId('hosting_account_id')->nullable()->constrained('hosting_accounts')->cascadeOnDelete();
            $table->string('target_path');
            $table->string('status')->default('pending');
            $table->unsignedInteger('scanned_files_count')->default(0);
            $table->unsignedInteger('infected_files_count')->default(0);
            $table->json('results')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['server_id', 'status']);
        });

        Schema::create('quarantined_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('security_scan_id')->constrained('security_scans')->cascadeOnDelete();
            $table->string('original_path');
            $table->string('quarantine_path');
            $table->string('threat_name');
            $table->string('status')->default('quarantined');
            $table->timestamp('quarantined_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quarantined_files');
        Schema::dropIfExists('security_scans');
        Schema::dropIfExists('waf_configurations');
    }
};
