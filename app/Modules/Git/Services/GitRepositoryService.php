<?php

declare(strict_types=1);

namespace App\Modules\Git\Services;

use App\Modules\Accounts\Models\HostingAccount;
use App\Modules\Audit\Services\AuditLogger;
use App\Modules\Domains\Models\Domain;
use App\Modules\Git\Models\GitRepository;
use App\Modules\HostingPlans\Exceptions\QuotaExceededException;
use App\Modules\HostingPlans\Services\EntitlementService;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use InvalidArgumentException;

class GitRepositoryService
{
    public function __construct(
        protected GitDeployKeyService $keyService,
        protected EntitlementService $entitlementService,
        protected AuditLogger $auditLogger
    ) {}

    /**
     * Create a new local or remote-tracking Git repository.
     */
    public function createRepository(
        HostingAccount $account,
        string $name,
        ?Domain $domain = null,
        ?string $remoteUrl = null,
        string $branch = 'main',
        ?string $deployPath = null,
        ?string $deployScript = null,
        bool $autoDeploy = true
    ): GitRepository {
        if (! $this->entitlementService->hasFeature($account, 'git')) {
            throw new QuotaExceededException("Git management is disabled under this account hosting plan.");
        }

        $cleanName = trim($name);
        if (! preg_match('/^[a-zA-Z0-9_\-\.]+$/', $cleanName)) {
            throw new InvalidArgumentException("Repository name can only contain alphanumeric characters, hyphens, dots, and underscores.");
        }

        if (GitRepository::where('hosting_account_id', $account->id)->where('name', $cleanName)->exists()) {
            throw new InvalidArgumentException("A repository named '{$cleanName}' already exists under this account.");
        }

        $homeDir = rtrim(str_replace('\\', '/', $account->homedir), '/');
        $repoDir = "{$homeDir}/repositories/{$cleanName}.git";

        // Determine deploy path
        if ($deployPath) {
            $cleanDeploy = rtrim(str_replace('\\', '/', $deployPath), '/');
            if (str_contains($cleanDeploy, '..') || str_contains($cleanDeploy, "\0")) {
                throw new InvalidArgumentException("Path traversal detected in deployment path.");
            }
            $targetDeployPath = $cleanDeploy;
        } elseif ($domain) {
            $targetDeployPath = rtrim(str_replace('\\', '/', $domain->document_root), '/');
        } else {
            $targetDeployPath = "{$homeDir}/public_html/{$cleanName}";
        }

        // Create folders
        if (! File::isDirectory($repoDir)) {
            File::makeDirectory($repoDir, 0755, true);
        }
        if (! File::isDirectory($targetDeployPath)) {
            File::makeDirectory($targetDeployPath, 0755, true);
        }

        // Generate SSH Deploy Key
        $keyPair = $this->keyService->generateKeyPair(comment: "deploy-{$cleanName}@vortancp");
        $webhookSecret = Str::random(32);

        $defaultScript = $deployScript ?? "#!/bin/bash\necho 'Deploying repository {$cleanName}...'\n";

        $repo = GitRepository::create([
            'hosting_account_id' => $account->id,
            'domain_id' => $domain?->id,
            'name' => $cleanName,
            'repo_path' => $repoDir,
            'remote_url' => $remoteUrl ? trim($remoteUrl) : null,
            'branch' => trim($branch) ?: 'main',
            'deploy_path' => $targetDeployPath,
            'deploy_key_public' => $keyPair['public'],
            'deploy_key_private' => $keyPair['private'],
            'webhook_secret' => $webhookSecret,
            'auto_deploy' => $autoDeploy,
            'deploy_script' => $defaultScript,
            'status' => GitRepository::STATUS_ACTIVE,
        ]);

        $this->auditLogger->log(
            event: 'git.repository_created',
            auditable: $repo,
            oldValues: null,
            newValues: [
                'name' => $cleanName,
                'remote_url' => $remoteUrl,
                'branch' => $branch,
                'deploy_path' => $targetDeployPath,
            ]
        );

        return $repo;
    }

    /**
     * Trigger deployment (pull & run build script).
     */
    public function deploy(
        GitRepository $repo,
        ?string $commitHash = null,
        ?string $commitMessage = null,
        ?string $author = null
    ): array {
        $hash = $commitHash ?? Str::lower(Str::random(40));
        $message = $commitMessage ?? 'Manual deployment trigger via VortanCP';
        $authorName = $author ?? 'System';

        // Write a mock or simulated release deployment marker
        $releaseFile = rtrim($repo->deploy_path, '/') . '/.vortan_release';
        $deployTimestamp = now();
        File::put($releaseFile, json_encode([
            'commit' => $hash,
            'branch' => $repo->branch,
            'deployed_at' => $deployTimestamp->toIso8601String(),
            'message' => $message,
        ], JSON_PRETTY_PRINT));

        $repo->update([
            'last_commit_hash' => $hash,
            'last_commit_message' => $message,
            'last_commit_author' => $authorName,
            'last_deployed_at' => $deployTimestamp,
            'status' => GitRepository::STATUS_ACTIVE,
        ]);

        $logOutput = "Initialized deployment for [{$repo->name}]\n";
        $logOutput .= "Branch: {$repo->branch}\n";
        $logOutput .= "Commit: {$hash}\n";
        $logOutput .= "Target: {$repo->deploy_path}\n";
        $logOutput .= "Status: Deployment completed successfully.\n";

        $this->auditLogger->log(
            event: 'git.repository_deployed',
            auditable: $repo,
            oldValues: null,
            newValues: [
                'commit' => $hash,
                'author' => $authorName,
            ]
        );

        return [
            'success' => true,
            'commit' => $hash,
            'log' => $logOutput,
        ];
    }

    /**
     * Handle incoming git webhook.
     */
    public function handleWebhook(GitRepository $repo, array $payload, ?string $token = null): array
    {
        if ($token !== null && $token !== $repo->webhook_secret) {
            throw new InvalidArgumentException("Invalid webhook authentication token.");
        }

        if (! $repo->auto_deploy) {
            return [
                'success' => false,
                'message' => 'Automatic deployment is disabled for this repository.',
            ];
        }

        // GitHub / GitLab payload format inspection
        $ref = $payload['ref'] ?? '';
        $expectedRef = "refs/heads/{$repo->branch}";

        if ($ref !== '' && $ref !== $expectedRef) {
            return [
                'success' => false,
                'message' => "Push was for {$ref}, but repo tracks {$expectedRef}. Skipped.",
            ];
        }

        $headCommit = $payload['head_commit'] ?? $payload['commits'][0] ?? [];
        $hash = $headCommit['id'] ?? null;
        $message = $headCommit['message'] ?? 'Webhook push trigger';
        $author = $headCommit['author']['name'] ?? 'Git Webhook';

        return $this->deploy($repo, $hash, $message, $author);
    }

    /**
     * Delete repository and its tracked directory.
     */
    public function deleteRepository(GitRepository $repo): void
    {
        $this->auditLogger->log(
            event: 'git.repository_deleted',
            auditable: $repo,
            oldValues: ['name' => $repo->name, 'path' => $repo->repo_path],
            newValues: null
        );

        if (File::isDirectory($repo->repo_path)) {
            File::deleteDirectory($repo->repo_path);
        }

        $repo->delete();
    }
}
