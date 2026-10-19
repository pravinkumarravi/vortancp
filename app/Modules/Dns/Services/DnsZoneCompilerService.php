<?php

declare(strict_types=1);

namespace App\Modules\Dns\Services;

use App\Modules\Dns\Models\DnsRecord;
use App\Modules\Dns\Models\DnsZone;

class DnsZoneCompilerService
{
    /**
     * Compile an RFC 1035 compliant BIND zone file representation.
     */
    public function compile(DnsZone $zone): string
    {
        $origin = rtrim($zone->name, '.') . '.';
        $primaryNs = rtrim($zone->primary_ns, '.') . '.';
        $adminEmail = str_replace('@', '.', rtrim($zone->admin_email, '.')) . '.';

        $out = [];
        $out[] = "; -------------------------------------------------------------";
        $out[] = "; VortanCP Managed DNS Zone: {$zone->name}";
        $out[] = "; Serial: {$zone->serial} | Generated: " . now()->toIso8601String();
        $out[] = "; -------------------------------------------------------------\n";
        $out[] = "\$ORIGIN {$origin}";
        $out[] = "\$TTL {$zone->minimum_ttl}\n";

        // SOA Record
        $out[] = sprintf(
            "@ IN SOA %s %s (\n    %-12s ; Serial\n    %-12s ; Refresh (3h)\n    %-12s ; Retry (1h)\n    %-12s ; Expire (1w)\n    %-12s ; Minimum TTL (1h)\n)\n",
            $primaryNs,
            $adminEmail,
            $zone->serial,
            $zone->refresh_sec,
            $zone->retry_sec,
            $zone->expire_sec,
            $zone->minimum_ttl
        );

        // Group records by type for neat organization
        $records = $zone->records()->get();

        // 1. NS records
        $nsRecords = $records->where('type', DnsRecord::TYPE_NS);
        if ($nsRecords->isNotEmpty()) {
            $out[] = "; --- Name Servers ---";
            foreach ($nsRecords as $r) {
                $target = str_ends_with($r->content, '.') ? $r->content : "{$r->content}.";
                $out[] = sprintf("%-24s %-6s IN NS    %s", $r->name, $r->ttl, $target);
            }
            $out[] = "";
        }

        // 2. MX records
        $mxRecords = $records->where('type', DnsRecord::TYPE_MX)->sortBy('priority');
        if ($mxRecords->isNotEmpty()) {
            $out[] = "; --- Mail Exchangers ---";
            foreach ($mxRecords as $r) {
                $prio = $r->priority ?? 10;
                $target = str_ends_with($r->content, '.') ? $r->content : "{$r->content}.";
                $out[] = sprintf("%-24s %-6s IN MX    %-4s %s", $r->name, $r->ttl, $prio, $target);
            }
            $out[] = "";
        }

        // 3. A & AAAA records
        $addressRecords = $records->whereIn('type', [DnsRecord::TYPE_A, DnsRecord::TYPE_AAAA]);
        if ($addressRecords->isNotEmpty()) {
            $out[] = "; --- Host Addresses ---";
            foreach ($addressRecords as $r) {
                $out[] = sprintf("%-24s %-6s IN %-5s %s", $r->name, $r->ttl, $r->type, $r->content);
            }
            $out[] = "";
        }

        // 4. CNAME records
        $cnameRecords = $records->where('type', DnsRecord::TYPE_CNAME);
        if ($cnameRecords->isNotEmpty()) {
            $out[] = "; --- Canonical Names ---";
            foreach ($cnameRecords as $r) {
                $out[] = sprintf("%-24s %-6s IN CNAME %s", $r->name, $r->ttl, $r->content);
            }
            $out[] = "";
        }

        // 5. TXT records
        $txtRecords = $records->where('type', DnsRecord::TYPE_TXT);
        if ($txtRecords->isNotEmpty()) {
            $out[] = "; --- Text & Verification Records ---";
            foreach ($txtRecords as $r) {
                $escaped = addcslashes($r->content, '"\\');
                $out[] = sprintf("%-24s %-6s IN TXT   \"%s\"", $r->name, $r->ttl, $escaped);
            }
            $out[] = "";
        }

        // 6. Other records (SRV, CAA)
        $otherRecords = $records->whereIn('type', [DnsRecord::TYPE_SRV, DnsRecord::TYPE_CAA]);
        if ($otherRecords->isNotEmpty()) {
            $out[] = "; --- Service & Certificate Authority Records ---";
            foreach ($otherRecords as $r) {
                if ($r->type === DnsRecord::TYPE_SRV) {
                    $out[] = sprintf("%-24s %-6s IN SRV   %s %s %s %s", $r->name, $r->ttl, $r->priority ?? 0, $r->weight ?? 0, $r->port ?? 0, $r->content);
                } else {
                    $out[] = sprintf("%-24s %-6s IN CAA   %s", $r->name, $r->ttl, $r->content);
                }
            }
            $out[] = "";
        }

        return implode("\n", $out);
    }
}
