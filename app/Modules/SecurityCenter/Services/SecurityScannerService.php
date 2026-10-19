<?php

declare(strict_types=1);

namespace App\Modules\SecurityCenter\Services;

use App\Modules\Accounts\Models\HostingAccount;
use App\Modules\Audit\Services\AuditLogger;
use App\Modules\SecurityCenter\Models\QuarantinedFile;
use App\Modules\SecurityCenter\Models\SecurityScan;
use App\Modules\Servers\Models\Server;
use Illuminate\Support\Facades\File;
use RuntimeException;

class SecurityScannerService
{
    /**
     * Malware signatures pattern bank.
     */
    protected const SIGNATURES = [
        'PHP.Webshell.Generic.EvalB64' => '/eval\s*\(\s*base64_decode\s*\(/i',
        'PHP.Backdoor.AssertPayload' => '/assert\s*\(\s*\$_(POST|GET|REQUEST|COOKIE)/i',
        'PHP.Webshell.CommandExec' => '/shell_exec\s*\(\s*\$_(POST|GET|REQUEST)/i',
        'PHP.Webshell.SystemExec' => '/system\s*\(\s*\$_(POST|GET|REQUEST)/i',
        'PHP.Backdoor.PregReplaceE' => '/preg_replace\s*\(\s*[\'"].*\/e[\'"]/i',
        'PHP.Obfuscated.GunzipB64' => '/gzinflate\s*\(\s*base64_decode\s*\(/i',
    ];

    public function __construct(
        protected AuditLogger $auditLogger
    ) {}

    /**
     * Perform file integrity and signature malware scan on directory.
     */
    public function scanDirectory(
        Server $server,
        ?HostingAccount $account,
        string $targetPath
    ): SecurityScan {
        $scan = SecurityScan::create([
            'server_id' => $server->id,
            'hosting_account_id' => $account?->id,
            'target_path' => $targetPath,
            'status' => SecurityScan::STATUS_RUNNING,
            'scanned_files_count' => 0,
            'infected_files_count' => 0,
        ]);

        $quarantineBase = storage_path('app/quarantine');
        if (! File::isDirectory($quarantineBase)) {
            File::makeDirectory($quarantineBase, 0755, true);
        }

        $scanned = 0;
        $infected = 0;
        $results = [];

        if (File::isDirectory($targetPath)) {
            $files = File::allFiles($targetPath);

            foreach ($files as $file) {
                $scanned++;
                $filePath = str_replace('\\', '/', $file->getRealPath());
                $ext = strtolower($file->getExtension());

                // Inspect scripts and text files
                if (in_array($ext, ['php', 'phtml', 'php5', 'php7', 'inc', 'js', 'sh', 'pl', 'py'], true)) {
                    $content = @file_get_contents($filePath);
                    if ($content !== false) {
                        foreach (self::SIGNATURES as $threatName => $regex) {
                            if (preg_match($regex, $content)) {
                                $infected++;
                                $quarantineFileName = "scan_{$scan->id}_" . md5($filePath) . "_{$file->getFilename()}.quarantine";
                                $quarantineDest = str_replace('\\', '/', "{$quarantineBase}/{$quarantineFileName}");

                                // Move infected file into quarantine
                                File::move($file->getRealPath(), $quarantineDest);

                                QuarantinedFile::create([
                                    'security_scan_id' => $scan->id,
                                    'original_path' => $filePath,
                                    'quarantine_path' => $quarantineDest,
                                    'threat_name' => $threatName,
                                    'status' => QuarantinedFile::STATUS_QUARANTINED,
                                    'quarantined_at' => now(),
                                ]);

                                $results[] = [
                                    'file' => $filePath,
                                    'threat' => $threatName,
                                    'action' => 'quarantined',
                                ];
                                break;
                            }
                        }
                    }
                }
            }
        }

        $finalStatus = $infected > 0 ? SecurityScan::STATUS_INFECTED : SecurityScan::STATUS_COMPLETED;
        $scan->update([
            'status' => $finalStatus,
            'scanned_files_count' => $scanned,
            'infected_files_count' => $infected,
            'results' => $results,
            'completed_at' => now(),
        ]);

        $this->auditLogger->log(
            event: 'security.scan_completed',
            auditable: $scan,
            oldValues: null,
            newValues: [
                'target_path' => $targetPath,
                'scanned_files' => $scanned,
                'infected_files' => $infected,
                'status' => $finalStatus,
            ]
        );

        return $scan;
    }

    /**
     * Restore a quarantined file back to its original location.
     */
    public function restoreFile(QuarantinedFile $file): void
    {
        if (! File::exists($file->quarantine_path)) {
            throw new RuntimeException("Quarantine file not found on disk.");
        }

        $destDir = dirname($file->original_path);
        if (! File::isDirectory($destDir)) {
            File::makeDirectory($destDir, 0755, true);
        }

        File::move($file->quarantine_path, $file->original_path);

        $file->update([
            'status' => QuarantinedFile::STATUS_RESTORED,
        ]);

        $this->auditLogger->log(
            event: 'security.file_restored',
            auditable: $file,
            oldValues: ['status' => QuarantinedFile::STATUS_QUARANTINED],
            newValues: ['status' => QuarantinedFile::STATUS_RESTORED]
        );
    }

    /**
     * Permanently delete a quarantined file.
     */
    public function deleteFile(QuarantinedFile $file): void
    {
        if (File::exists($file->quarantine_path)) {
            File::delete($file->quarantine_path);
        }

        $file->update([
            'status' => QuarantinedFile::STATUS_DELETED,
        ]);

        $this->auditLogger->log(
            event: 'security.file_permanently_deleted',
            auditable: $file,
            oldValues: ['status' => QuarantinedFile::STATUS_QUARANTINED],
            newValues: ['status' => QuarantinedFile::STATUS_DELETED]
        );
    }
}
