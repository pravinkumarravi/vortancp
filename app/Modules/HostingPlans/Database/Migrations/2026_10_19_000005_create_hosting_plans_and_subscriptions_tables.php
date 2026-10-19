<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hosting_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reseller_id')->nullable()->constrained('resellers')->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);

            // Quotas & Entitlements
            $table->unsignedInteger('max_domains')->default(1);
            $table->unsignedInteger('max_subdomains')->default(5);
            $table->unsignedBigInteger('max_disk_mb')->default(10240); // 10 GB
            $table->unsignedBigInteger('max_bandwidth_mb')->default(102400); // 100 GB
            $table->unsignedInteger('max_databases')->default(5);
            $table->unsignedInteger('max_mailboxes')->default(10);
            $table->unsignedInteger('max_ftp_accounts')->default(5);
            $table->unsignedInteger('max_cron_jobs')->default(5);

            // Feature Flags
            $table->boolean('has_ssh_access')->default(false);
            $table->boolean('has_ssl')->default(true);
            $table->boolean('has_git')->default(true);
            $table->boolean('has_dns_management')->default(true);
            $table->boolean('has_wordpress_toolkit')->default(true);
            $table->timestamps();

            $table->index(['reseller_id', 'is_active']);
        });

        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('hosting_plan_id')->constrained('hosting_plans')->restrictOnDelete();
            $table->foreignId('server_id')->constrained('servers')->restrictOnDelete();
            $table->foreignId('hosting_account_id')->nullable()->constrained('hosting_accounts')->nullOnDelete();
            $table->string('status')->default('active'); // active, suspended, expired, cancelled
            $table->timestamp('starts_at')->useCurrent();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['customer_id', 'status']);
        });

        // Add subscription_id reference to hosting_accounts if desired
        Schema::table('hosting_accounts', function (Blueprint $table) {
            $table->foreignId('subscription_id')->nullable()->after('customer_id')->constrained('subscriptions')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('hosting_accounts', function (Blueprint $table) {
            $table->dropForeign(['subscription_id']);
            $table->dropColumn('subscription_id');
        });

        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('hosting_plans');
    }
};
