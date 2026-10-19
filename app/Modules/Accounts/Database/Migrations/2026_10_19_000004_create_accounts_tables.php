<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resellers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->string('status')->default('active'); // active, suspended
            $table->unsignedInteger('max_customers')->default(50);
            $table->unsignedBigInteger('max_disk_mb')->default(102400); // 100 GB
            $table->unsignedBigInteger('max_bandwidth_mb')->default(1048576); // 1 TB
            $table->unsignedInteger('max_domains')->default(100);
            $table->timestamps();

            $table->index('status');
        });

        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reseller_id')->nullable()->constrained('resellers')->nullOnDelete();
            $table->string('name');
            $table->string('company_name')->nullable();
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->string('status')->default('active'); // active, suspended, locked
            $table->timestamps();

            $table->index(['reseller_id', 'status']);
        });

        Schema::create('hosting_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('server_id')->constrained('servers')->restrictOnDelete();
            $table->string('username')->unique(); // Linux system username
            $table->string('primary_domain')->unique();
            $table->string('status')->default('active'); // active, suspended, provisioning, failed
            $table->unsignedInteger('uid')->nullable();
            $table->unsignedInteger('gid')->nullable();
            $table->string('homedir');
            $table->unsignedBigInteger('disk_used_mb')->default(0);
            $table->unsignedBigInteger('bandwidth_used_mb')->default(0);
            $table->string('suspended_reason')->nullable();
            $table->timestamps();

            $table->index(['customer_id', 'status']);
            $table->index(['server_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hosting_accounts');
        Schema::dropIfExists('customers');
        Schema::dropIfExists('resellers');
    }
};
