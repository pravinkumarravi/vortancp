<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_security_policies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hosting_account_id')->constrained('hosting_accounts')->cascadeOnDelete();
            $table->foreignId('mail_domain_id')->nullable()->constrained('mail_domains')->cascadeOnDelete();
            $table->boolean('spam_filter_enabled')->default(true);
            $table->boolean('greylisting_enabled')->default(true);
            $table->float('required_score')->default(5.0);
            $table->float('reject_score')->default(15.0);
            $table->boolean('auto_delete_spam')->default(false);
            $table->string('spam_subject_tag')->default('***SPAM***');
            $table->json('allowlist')->nullable();
            $table->json('blocklist')->nullable();
            $table->json('rbl_servers')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_security_policies');
    }
};
