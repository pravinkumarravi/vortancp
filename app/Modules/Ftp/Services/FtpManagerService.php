<?php

declare(strict_types=1);

namespace App\Modules\Ftp\Services;

use App\Modules\Accounts\Models\HostingAccount;
use App\Modules\Audit\Services\AuditLogger;
use App\Modules\Ftp\Models\FtpAccount;
use App\Modules\HostingPlans\Services\EntitlementService;
use Illuminate\Support\Facades\Hash;
use InvalidArgumentException;

class FtpManagerService
{
    public function __construct(
        protected EntitlementService $entitlementService,
        protected AuditLogger $auditLogger
    ) {}

    /**
     * Create an FTP account bounded to a hosting account and chrooted directory.
     */
    public function createAccount(
        HostingAccount $account,
        string $loginPart,
        string $password,
        string $relativePath = 'public_html',
        ?int $quotaMb = null,
        ?int $uploadKb = null,
        ?int $downloadKb = null
    ): FtpAccount {
        $this->entitlementService->assertCan($account, 'ftp', $account->ftpAccounts()->count());

        $loginPart = trim(strtolower($loginPart));
        if (empty($loginPart) || preg_match('/[^a-z0-9_\-\.]/', $loginPart)) {
            throw new InvalidArgumentException("Invalid FTP username format. Use lowercase alphanumeric characters, dots, dashes, and underscores.");
        }

        // Format username as user@primary_domain if domain suffix is not provided
        $username = str_contains($loginPart, '@')
            ? $loginPart
            : "{$loginPart}@{$account->primary_domain}";

        if (FtpAccount::where('username', $username)->exists()) {
            throw new InvalidArgumentException("FTP account '{$username}' already exists.");
        }

        // Ensure safe chroot jail within account's home root
        $accountHome = rtrim($account->homedir ?: "/home/{$account->username}", '/');
        $cleanPath = trim(str_replace('\\', '/', $relativePath), '/');

        if (str_contains($cleanPath, '..') || str_contains($cleanPath, "\0")) {
            throw new InvalidArgumentException("Path traversal or null bytes detected in FTP homedir.");
        }

        $fullHomedir = $cleanPath === '' ? $accountHome : "{$accountHome}/{$cleanPath}";

        $ftpAccount = FtpAccount::create([
            'hosting_account_id' => $account->id,
            'username' => $username,
            'password_hash' => Hash::make($password),
            'homedir' => $fullHomedir,
            'quota_mb' => $quotaMb,
            'upload_bandwidth_kb' => $uploadKb,
            'download_bandwidth_kb' => $downloadKb,
            'is_active' => true,
        ]);

        $this->auditLogger->log(
            event: 'ftp.account_created',
            auditable: $ftpAccount,
            oldValues: null,
            newValues: ['username' => $username, 'homedir' => $fullHomedir]
        );

        return $ftpAccount;
    }

    /**
     * Update the password for an FTP account.
     */
    public function updatePassword(FtpAccount $ftpAccount, string $newPassword): void
    {
        if (strlen($newPassword) < 8) {
            throw new InvalidArgumentException("FTP password must be at least 8 characters.");
        }

        $ftpAccount->update([
            'password_hash' => Hash::make($newPassword),
        ]);

        $this->auditLogger->log(
            event: 'ftp.password_updated',
            auditable: $ftpAccount,
            oldValues: null,
            newValues: ['username' => $ftpAccount->username]
        );
    }

    /**
     * Toggle active status.
     */
    public function toggleStatus(FtpAccount $ftpAccount): void
    {
        $oldStatus = $ftpAccount->is_active;
        $ftpAccount->update([
            'is_active' => ! $ftpAccount->is_active,
        ]);

        $this->auditLogger->log(
            event: 'ftp.status_toggled',
            auditable: $ftpAccount,
            oldValues: ['is_active' => $oldStatus],
            newValues: ['is_active' => $ftpAccount->is_active]
        );
    }

    /**
     * Delete an FTP account.
     */
    public function deleteAccount(FtpAccount $ftpAccount): void
    {
        $this->auditLogger->log(
            event: 'ftp.account_deleted',
            auditable: $ftpAccount,
            oldValues: ['username' => $ftpAccount->username, 'homedir' => $ftpAccount->homedir],
            newValues: null
        );

        $ftpAccount->delete();
    }

    /**
     * Compile Pure-FTPd virtual user entry format:
     * account:password:uid:gid:gecos:home:upload_rate:download_rate:max_files:max_size:allow_local_ip:deny_local_ip:allow_client_ip:deny_client_ip:time_range:ratio_upload:ratio_download
     */
    public function compilePureFtpdPasswdEntry(FtpAccount $ftpAccount, int $uid = 1000, int $gid = 1000): string
    {
        $maxSize = $ftpAccount->quota_mb ? (string) $ftpAccount->quota_mb : '';
        $upRate = $ftpAccount->upload_bandwidth_kb ? (string) $ftpAccount->upload_bandwidth_kb : '';
        $downRate = $ftpAccount->download_bandwidth_kb ? (string) $ftpAccount->download_bandwidth_kb : '';

        return sprintf(
            "%s:%s:%d:%d::%s:%s:%s::%s:::::::",
            $ftpAccount->username,
            $ftpAccount->password_hash,
            $uid,
            $gid,
            $ftpAccount->homedir,
            $upRate,
            $downRate,
            $maxSize
        );
    }

    /**
     * Compile ProFTPD virtual user auth line:
     * username:password_hash:uid:gid:gecos:homedir:shell
     */
    public function compileProftpdUserEntry(FtpAccount $ftpAccount, int $uid = 1000, int $gid = 1000): string
    {
        return sprintf(
            "%s:%s:%d:%d:%s:%s:%s",
            $ftpAccount->username,
            $ftpAccount->password_hash,
            $uid,
            $gid,
            "Vortan Virtual User",
            $ftpAccount->homedir,
            "/bin/false"
        );
    }
}
