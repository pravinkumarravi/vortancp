<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mail_domains', function (Blueprint $table) {
            $table->id();
            $table->foreignId('domain_id')->constrained('domains')->cascadeOnDelete();
            $table->foreignId('hosting_account_id')->constrained('hosting_accounts')->cascadeOnDelete();
            $table->string('name')->unique();
            $table->string('dkim_selector')->default('default');
            $table->text('dkim_private_key')->nullable();
            $table->text('dkim_public_key')->nullable();
            $table->string('status')->default('active'); // active, suspended
            $table->timestamps();

            $table->index(['hosting_account_id', 'status']);
        });

        Schema::create('mailboxes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mail_domain_id')->constrained('mail_domains')->cascadeOnDelete();
            $table->foreignId('hosting_account_id')->constrained('hosting_accounts')->cascadeOnDelete();
            $table->string('email')->unique();
            $table->string('local_part'); // e.g. info, support
            $table->string('password_hash');
            $table->unsignedInteger('quota_mb')->default(1024); // 1GB default
            $table->unsignedInteger('used_mb')->default(0);
            $table->string('status')->default('active'); // active, suspended
            $table->timestamps();

            $table->index(['mail_domain_id', 'local_part']);
            $table->index('status');
        });

        Schema::create('mail_aliases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mail_domain_id')->constrained('mail_domains')->cascadeOnDelete();
            $table->string('source_email');
            $table->string('destination_email');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('source_email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mail_aliases');
        Schema::dropIfExists('mailboxes');
        Schema::dropIfExists('mail_domains');
    }
};
