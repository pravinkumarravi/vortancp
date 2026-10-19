<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('installed_apps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hosting_account_id')->constrained('hosting_accounts')->cascadeOnDelete();
            $table->foreignId('domain_id')->constrained('domains')->cascadeOnDelete();
            $table->string('app_slug');
            $table->string('app_name');
            $table->string('version');
            $table->string('install_path');
            $table->string('site_url');
            $table->string('db_name')->nullable();
            $table->string('db_user')->nullable();
            $table->string('admin_email');
            $table->string('status')->default('installed'); // installed, upgrading, failed
            $table->timestamp('installed_at')->nullable();
            $table->timestamps();

            $table->index(['hosting_account_id', 'domain_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('installed_apps');
    }
};
