<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ssl_certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('domain_id')->constrained('domains')->cascadeOnDelete();
            $table->foreignId('hosting_account_id')->constrained('hosting_accounts')->cascadeOnDelete();
            $table->string('issuer')->default("Let's Encrypt");
            $table->string('subject_dn');
            $table->json('sans')->nullable(); // Subject Alternative Names
            $table->longText('certificate');
            $table->longText('private_key');
            $table->longText('chain')->nullable();
            $table->timestamp('valid_from');
            $table->timestamp('valid_until');
            $table->boolean('auto_renew')->default(true);
            $table->string('status')->default('active'); // active, expired, revoked
            $table->timestamps();

            $table->index(['domain_id', 'status']);
            $table->index('valid_until');
        });

        Schema::create('ssl_challenges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('domain_id')->constrained('domains')->cascadeOnDelete();
            $table->string('token')->index();
            $table->text('payload');
            $table->string('type')->default('http-01'); // http-01, dns-01
            $table->timestamp('expires_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ssl_challenges');
        Schema::dropIfExists('ssl_certificates');
    }
};
