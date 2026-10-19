<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('php_installations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('server_id')->constrained('servers')->cascadeOnDelete();
            $table->string('version'); // 8.4, 8.3, 8.2, 8.1
            $table->string('bin_path'); // /usr/bin/php8.4
            $table->string('fpm_service'); // php8.4-fpm
            $table->string('status')->default('active'); // active, inactive
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->unique(['server_id', 'version']);
        });

        Schema::create('php_pools', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hosting_account_id')->constrained('hosting_accounts')->cascadeOnDelete();
            $table->string('version')->default('8.4');
            $table->string('socket_path');
            $table->string('pm_type')->default('ondemand'); // static, dynamic, ondemand
            $table->unsignedSmallInteger('pm_max_children')->default(10);
            $table->string('memory_limit')->default('256M');
            $table->unsignedSmallInteger('max_execution_time')->default(60);
            $table->string('upload_max_filesize')->default('64M');
            $table->string('post_max_size')->default('64M');
            $table->boolean('display_errors')->default(false);
            $table->timestamps();

            $table->unique(['hosting_account_id', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('php_pools');
        Schema::dropIfExists('php_installations');
    }
};
