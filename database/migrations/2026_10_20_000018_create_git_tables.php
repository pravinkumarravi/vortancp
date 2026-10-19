<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('git_repositories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hosting_account_id')->constrained('hosting_accounts')->cascadeOnDelete();
            $table->foreignId('domain_id')->nullable()->constrained('domains')->nullOnDelete();
            $table->string('name');
            $table->string('repo_path');
            $table->string('remote_url')->nullable();
            $table->string('branch')->default('main');
            $table->string('deploy_path');
            $table->text('deploy_key_public')->nullable();
            $table->text('deploy_key_private')->nullable();
            $table->string('webhook_secret')->nullable();
            $table->boolean('auto_deploy')->default(true);
            $table->text('deploy_script')->nullable();
            $table->string('last_commit_hash')->nullable();
            $table->text('last_commit_message')->nullable();
            $table->string('last_commit_author')->nullable();
            $table->timestamp('last_deployed_at')->nullable();
            $table->string('status')->default('active'); // active, cloning, failed
            $table->timestamps();

            $table->unique(['hosting_account_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('git_repositories');
    }
};
