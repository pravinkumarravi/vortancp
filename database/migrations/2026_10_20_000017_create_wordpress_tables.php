<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wordpress_instances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hosting_account_id')->constrained('hosting_accounts')->cascadeOnDelete();
            $table->foreignId('domain_id')->constrained('domains')->cascadeOnDelete();
            $table->string('site_title');
            $table->string('site_url');
            $table->string('path');
            $table->string('db_name');
            $table->string('db_user');
            $table->string('wp_version')->default('6.7.1');
            $table->string('admin_email');
            $table->string('status')->default('active'); // active, maintenance, needs_update
            $table->boolean('auto_update_core')->default(true);
            $table->boolean('auto_update_plugins')->default(false);
            $table->boolean('xmlrpc_blocked')->default(true);
            $table->boolean('file_editing_disabled')->default(true);
            $table->string('table_prefix')->default('wp_');
            $table->timestamp('last_scanned_at')->nullable();
            $table->timestamps();

            $table->index(['hosting_account_id', 'domain_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wordpress_instances');
    }
};
