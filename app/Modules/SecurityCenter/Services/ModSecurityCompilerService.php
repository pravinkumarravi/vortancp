<?php

declare(strict_types=1);

namespace App\Modules\SecurityCenter\Services;

use App\Modules\SecurityCenter\Models\WafConfiguration;

class ModSecurityCompilerService
{
    /**
     * Compile ModSecurity v3 / OWASP CRS configuration snippet for a domain.
     */
    public function compileSnippet(WafConfiguration $config): string
    {
        $lines = [];
        $lines[] = '# ==============================================================================';
        $lines[] = '# VortanCP ModSecurity & OWASP CRS v3 Web Application Firewall Configuration';
        $domainName = $config->domain?->domain ?? 'Global Default Policy';
        $lines[] = "# Target: {$domainName}";
        $lines[] = '# Generated at: ' . now()->toIso8601String();
        $lines[] = '# ==============================================================================';

        if (! $config->is_enabled) {
            $lines[] = 'SecRuleEngine Off';
            return implode("\n", $lines) . "\n";
        }

        $lines[] = 'SecRuleEngine On';
        $lines[] = 'SecRequestBodyAccess On';
        $lines[] = 'SecRequestBodyLimit 13107200';
        $lines[] = 'SecRequestBodyNoFilesLimit 131072';
        $lines[] = 'SecResponseBodyAccess Off';
        $lines[] = '';

        // OWASP CRS Core Variables
        $paranoia = max(1, min(4, $config->paranoia_level));
        $threshold = max(1, $config->anomaly_threshold);
        $lines[] = "# OWASP CRS Paranoia Level: {$paranoia}";
        $lines[] = "SecAction \"id:900000,phase:1,nolog,pass,t:none,setvar:tx.paranoia_level={$paranoia}\"";
        $lines[] = "# Inbound Anomaly Score Threshold: {$threshold}";
        $lines[] = "SecAction \"id:900110,phase:1,nolog,pass,t:none,setvar:tx.inbound_anomaly_score_threshold={$threshold}\"";
        $lines[] = '';

        // Protection modules toggles
        if (! $config->block_sqli) {
            $lines[] = '# SQLi protection disabled';
            $lines[] = 'SecRuleRemoveByTag "attack-sqli"';
        }

        if (! $config->block_xss) {
            $lines[] = '# XSS protection disabled';
            $lines[] = 'SecRuleRemoveByTag "attack-xss"';
        }

        if (! $config->block_rfi) {
            $lines[] = '# RFI/LFI protection disabled';
            $lines[] = 'SecRuleRemoveByTag "attack-rfi"';
            $lines[] = 'SecRuleRemoveByTag "attack-lfi"';
        }

        if (! $config->block_code_injection) {
            $lines[] = '# Code injection protection disabled';
            $lines[] = 'SecRuleRemoveByTag "attack-injection-php"';
            $lines[] = 'SecRuleRemoveByTag "attack-injection-generic"';
        }

        // Disabled Rule IDs exceptions
        $disabledIds = $config->disabled_rule_ids ?? [];
        if (! empty($disabledIds)) {
            $lines[] = '';
            $lines[] = '# Rule ID Exclusion Exceptions';
            foreach ($disabledIds as $ruleId) {
                $cleanId = (int) $ruleId;
                if ($cleanId > 0) {
                    $lines[] = "SecRuleRemoveById {$cleanId}";
                }
            }
        }

        // Custom Rule Directives
        if (! empty($config->custom_rules)) {
            $lines[] = '';
            $lines[] = '# Custom User Rules';
            $lines[] = trim($config->custom_rules);
        }

        $lines[] = '';
        return implode("\n", $lines) . "\n";
    }
}
