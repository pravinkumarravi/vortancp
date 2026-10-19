<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('domains', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hosting_account_id')->constrained('hosting_accounts')->cascadeOnDelete();
            $table->string('domain')->unique();
            $table->string('type')->default('addon'); // primary, addon, subdomain, alias
            $table->foreignId('parent_domain_id')->nullable()->constrained('domains')->nullOnDelete();
            $table->string('document_root');
            $table->string('php_version')->default('8.4');
            $table->string('status')->default('active'); // active, suspended, provisioning
            $table->boolean('force_https')->default(true);
            $table->string('www_redirect')->default('none'); // none, redirect_to_www, redirect_to_non_www
            $table->timestamps();

            $table->index(['hosting_account_id', 'type']);
            $table->index('status');
        });

        Schema::create('domain_redirects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('domain_id')->constrained('domains')->cascadeOnDelete();
            $table->string('path')->default('/');
            $table->string('target_url');
            $table->unsignedSmallInteger('status_code')->default(301); // 301 or 302
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('virtual_hosts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('domain_id')->constrained('domains')->cascadeOnDelete();
            $table->foreignId('server_id')->constrained('servers')->cascadeOnDelete();
            $table->string('webserver_type')->default('nginx'); // nginx, apache, openlitespeed
            $table->string('config_path');
            $table->boolean('is_ssl_enabled')->default(false);
            $table->timestamp('last_compiled_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('virtual_hosts');
        Schema::dropIfExists('domain_redirects');
        Schema::dropIfExists('domains');
    }
};
