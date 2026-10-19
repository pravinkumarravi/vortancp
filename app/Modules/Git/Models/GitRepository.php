<?php

declare(strict_types=1);

namespace App\Modules\Git\Models;

use App\Modules\Accounts\Models\HostingAccount;
use App\Modules\Domains\Models\Domain;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GitRepository extends Model
{
    use HasFactory;

    protected $table = 'git_repositories';

    public const STATUS_ACTIVE = 'active';
    public const STATUS_CLONING = 'cloning';
    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'hosting_account_id',
        'domain_id',
        'name',
        'repo_path',
        'remote_url',
        'branch',
        'deploy_path',
        'deploy_key_public',
        'deploy_key_private',
        'webhook_secret',
        'auto_deploy',
        'deploy_script',
        'last_commit_hash',
        'last_commit_message',
        'last_commit_author',
        'last_deployed_at',
        'status',
    ];

    protected $casts = [
        'auto_deploy' => 'boolean',
        'last_deployed_at' => 'datetime',
        'deploy_key_private' => 'encrypted',
    ];

    public function hostingAccount(): BelongsTo
    {
        return $this->belongsTo(HostingAccount::class);
    }

    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }

    public function isRemote(): bool
    {
        return !empty($this->remote_url);
    }
}
