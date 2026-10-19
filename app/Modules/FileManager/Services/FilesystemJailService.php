<?php

declare(strict_types=1);

namespace App\Modules\FileManager\Services;

use App\Modules\Accounts\Models\HostingAccount;
use App\Modules\Audit\Services\AuditLogger;
use Illuminate\Support\Facades\File;
use InvalidArgumentException;
use RuntimeException;
use ZipArchive;

class FilesystemJailService
{
    public function __construct(
        protected AuditLogger $auditLogger,
    ) {}

    /**
     * Resolve the base root directory for a hosting account.
     */
    public function getHomedir(HostingAccount $account): string
    {
        // If homedir is defined and points to an absolute path, use it.
        // In local/test environments without root /home privileges, map to storage/sandboxes/{username}
        if (app()->environment('testing') || !is_dir('/home')) {
            $base = storage_path("sandboxes/{$account->username}");
            if (!is_dir($base)) {
                File::makeDirectory($base, 0755, true, true);
                File::makeDirectory("{$base}/public_html", 0755, true, true);
            }
            return $base;
        }

        return $account->homedir ?: "/home/{$account->username}";
    }

    /**
     * Resolve and validate that a requested path stays strictly within the account's jail.
     *
     * @throws InvalidArgumentException
     */
    public function resolvePath(HostingAccount $account, string $relativePath): string
    {
        $homedir = rtrim($this->getHomedir($account), '/\\');

        // Explicitly block traversal tokens and null bytes
        if (str_contains($relativePath, '..') || str_contains($relativePath, "\0")) {
            throw new InvalidArgumentException("Security Violation: Path traversal escape attempt detected for [{$relativePath}].");
        }

        $cleanRelative = trim(str_replace('\\', '/', $relativePath), '/');
        $fullPath = $cleanRelative === '' ? $homedir : "{$homedir}/{$cleanRelative}";

        // Normalize directory separators
        $normalizedHome = str_replace('\\', '/', $homedir);
        $normalizedFull = str_replace('\\', '/', $fullPath);

        if (!str_starts_with($normalizedFull, $normalizedHome)) {
            throw new InvalidArgumentException("Security Violation: Path traversal escape attempt detected for [{$relativePath}].");
        }

        return $fullPath;
    }

    /**
     * List directory contents with metadata (type, size, permissions, modified).
     */
    public function listDirectory(HostingAccount $account, string $relativePath = '/'): array
    {
        $targetDir = $this->resolvePath($account, $relativePath);

        if (!is_dir($targetDir)) {
            throw new InvalidArgumentException("Directory [{$relativePath}] does not exist.");
        }

        $items = [];
        $files = scandir($targetDir);

        if ($files === false) {
            return [];
        }

        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $path = "{$targetDir}/{$file}";
            $isDir = is_dir($path);
            $size = $isDir ? 0 : (filesize($path) ?: 0);
            $mtime = filemtime($path) ?: time();
            $perms = substr(sprintf('%o', fileperms($path)), -4);

            $items[] = [
                'name' => $file,
                'path' => trim("{$relativePath}/{$file}", '/'),
                'is_dir' => $isDir,
                'size' => $size,
                'size_formatted' => $isDir ? '-' : $this->formatBytes($size),
                'permissions' => $perms,
                'modified_at' => date('Y-m-d H:i:s', $mtime),
                'extension' => $isDir ? 'dir' : strtolower(pathinfo($file, PATHINFO_EXTENSION)),
            ];
        }

        // Sort: directories first, then alphabetical
        usort($items, function ($a, $b) {
            if ($a['is_dir'] === $b['is_dir']) {
                return strcasecmp($a['name'], $b['name']);
            }
            return $a['is_dir'] ? -1 : 1;
        });

        return $items;
    }

    /**
     * Read file content securely.
     */
    public function readFile(HostingAccount $account, string $relativePath): string
    {
        $fullPath = $this->resolvePath($account, $relativePath);

        if (!is_file($fullPath)) {
            throw new InvalidArgumentException("File [{$relativePath}] does not exist.");
        }

        if (filesize($fullPath) > 5 * 1024 * 1024) {
            throw new InvalidArgumentException('File exceeds 5MB in-browser editing threshold.');
        }

        return file_get_contents($fullPath) ?: '';
    }

    /**
     * Write file content securely.
     */
    public function writeFile(HostingAccount $account, string $relativePath, string $content): void
    {
        $fullPath = $this->resolvePath($account, $relativePath);
        $dir = dirname($fullPath);

        if (!is_dir($dir)) {
            File::makeDirectory($dir, 0755, true, true);
        }

        file_put_contents($fullPath, $content);

        $this->auditLogger->log(
            event: 'file.written',
            auditable: $account,
            newValues: ['file' => $relativePath, 'bytes' => strlen($content)]
        );
    }

    /**
     * Create directory securely.
     */
    public function createDirectory(HostingAccount $account, string $relativePath): void
    {
        $fullPath = $this->resolvePath($account, $relativePath);

        if (is_dir($fullPath)) {
            throw new InvalidArgumentException("Directory [{$relativePath}] already exists.");
        }

        File::makeDirectory($fullPath, 0755, true, true);

        $this->auditLogger->log(
            event: 'directory.created',
            auditable: $account,
            newValues: ['directory' => $relativePath]
        );
    }

    /**
     * Delete file or directory recursively.
     */
    public function deleteItem(HostingAccount $account, string $relativePath): void
    {
        $fullPath = $this->resolvePath($account, $relativePath);
        $homedir = rtrim($this->getHomedir($account), '/\\');

        if ($fullPath === $homedir) {
            throw new InvalidArgumentException('Cannot delete the root home directory.');
        }

        if (is_dir($fullPath)) {
            File::deleteDirectory($fullPath);
        } elseif (is_file($fullPath)) {
            unlink($fullPath);
        } else {
            throw new InvalidArgumentException("Path [{$relativePath}] does not exist.");
        }

        $this->auditLogger->log(
            event: 'file.deleted',
            auditable: $account,
            oldValues: ['path' => $relativePath]
        );
    }

    /**
     * Rename file or directory.
     */
    public function renameItem(HostingAccount $account, string $oldPath, string $newPath): void
    {
        $source = $this->resolvePath($account, $oldPath);
        $target = $this->resolvePath($account, $newPath);

        if (!file_exists($source)) {
            throw new InvalidArgumentException("Source path [{$oldPath}] does not exist.");
        }

        if (file_exists($target)) {
            throw new InvalidArgumentException("Target path [{$newPath}] already exists.");
        }

        rename($source, $target);

        $this->auditLogger->log(
            event: 'file.renamed',
            auditable: $account,
            newValues: ['old' => $oldPath, 'new' => $newPath]
        );
    }

    /**
     * Extract a Zip archive within the account jail.
     */
    public function extractZip(HostingAccount $account, string $zipRelativePath, string $destinationRelativePath): void
    {
        $zipFile = $this->resolvePath($account, $zipRelativePath);
        $destDir = $this->resolvePath($account, $destinationRelativePath);

        if (!is_file($zipFile)) {
            throw new InvalidArgumentException("Zip file [{$zipRelativePath}] does not exist.");
        }

        if (!class_exists('ZipArchive')) {
            throw new RuntimeException('PHP Zip extension is not installed.');
        }

        $zip = new ZipArchive();
        if ($zip->open($zipFile) !== true) {
            throw new RuntimeException('Failed to open Zip archive.');
        }

        if (!is_dir($destDir)) {
            File::makeDirectory($destDir, 0755, true, true);
        }

        $zip->extractTo($destDir);
        $zip->close();

        $this->auditLogger->log(
            event: 'archive.extracted',
            auditable: $account,
            newValues: ['archive' => $zipRelativePath, 'destination' => $destinationRelativePath]
        );
    }

    protected function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) {
            return "{$bytes} B";
        } elseif ($bytes < 1048576) {
            return round($bytes / 1024, 1) . ' KB';
        } else {
            return round($bytes / 1048576, 1) . ' MB';
        }
    }
}
