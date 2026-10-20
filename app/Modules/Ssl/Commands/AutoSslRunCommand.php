<?php

declare(strict_types=1);

namespace App\Modules\Ssl\Commands;

use App\Modules\Ssl\Services\AutoSslFleetService;
use Illuminate\Console\Command;

class AutoSslRunCommand extends Command
{
    protected $signature = 'vortan:autossl-run {--domain= : Run for specific domain ID} {--account= : Run for specific hosting account ID}';
    protected $description = 'Automated cluster-wide SSL scanner issuing and renewing Let\'s Encrypt certificates';

    public function handle(AutoSslFleetService $autoSsl): int
    {
        $this->info('Starting VortanCP AutoSSL fleet scan...');

        $domainId = $this->option('domain') ? (int) $this->option('domain') : null;
        $accountId = $this->option('account') ? (int) $this->option('account') : null;

        $results = $autoSsl->runAutoSsl($domainId, $accountId);

        $this->line("Domains Scanned: {$results['total_scanned']}");
        $this->line("Certificates Issued: {$results['issued']}");
        $this->line("Certificates Renewed: {$results['renewed']}");
        if ($results['failed'] > 0) {
            $this->error("Failed Issuances: {$results['failed']}");
        }

        foreach ($results['results'] as $res) {
            $status = strtoupper($res['action']);
            $this->line("  [{$status}] {$res['domain']}: {$res['message']}");
        }

        $this->info('AutoSSL run completed successfully.');

        return self::SUCCESS;
    }
}
