<?php

declare(strict_types=1);

namespace App\Modules\Dns\Services;

use App\Modules\Audit\Services\AuditLogger;
use App\Modules\Dns\Models\DnsRecord;
use App\Modules\Dns\Models\DnsZone;
use App\Modules\Domains\Models\Domain;
use InvalidArgumentException;

class DnsZoneService
{
    public function __construct(
        protected DnsZoneCompilerService $compiler,
        protected AuditLogger $auditLogger,
    ) {}

    /**
     * Create a standard authoritative DNS zone with default hosting records for a domain.
     */
    public function createDefaultZoneForDomain(
        Domain $domain,
        string $serverIp,
        string $ns1 = 'ns1.vortan.local',
        string $ns2 = 'ns2.vortan.local',
    ): DnsZone {
        $zoneName = strtolower(trim($domain->domain));

        if (DnsZone::where('name', $zoneName)->exists()) {
            throw new InvalidArgumentException("A DNS zone for [{$zoneName}] already exists.");
        }

        $zone = DnsZone::create([
            'domain_id' => $domain->id,
            'hosting_account_id' => $domain->hosting_account_id,
            'name' => $zoneName,
            'status' => 'active',
            'serial' => (int) (date('Ymd') . '01'),
            'primary_ns' => $ns1,
            'admin_email' => "hostmaster.{$zoneName}",
            'refresh_sec' => 10800,
            'retry_sec' => 3600,
            'expire_sec' => 604800,
            'minimum_ttl' => 3600,
        ]);

        // Default Records:
        // 1. NS records
        $this->createRecordQuietly($zone, '@', DnsRecord::TYPE_NS, $ns1);
        $this->createRecordQuietly($zone, '@', DnsRecord::TYPE_NS, $ns2);

        // 2. A record for apex and mail
        $this->createRecordQuietly($zone, '@', DnsRecord::TYPE_A, $serverIp);
        $this->createRecordQuietly($zone, 'mail', DnsRecord::TYPE_A, $serverIp);

        // 3. CNAME records for www and ftp
        $this->createRecordQuietly($zone, 'www', DnsRecord::TYPE_CNAME, $zoneName . '.');
        $this->createRecordQuietly($zone, 'ftp', DnsRecord::TYPE_CNAME, $zoneName . '.');

        // 4. MX record
        $this->createRecordQuietly($zone, '@', DnsRecord::TYPE_MX, "mail.{$zoneName}.", 3600, 10);

        // 5. SPF TXT record
        $this->createRecordQuietly($zone, '@', DnsRecord::TYPE_TXT, 'v=spf1 a mx ~all');

        $this->auditLogger->log(
            event: 'dns.zone_created',
            auditable: $zone,
            newValues: ['name' => $zoneName, 'server_ip' => $serverIp]
        );

        return $zone;
    }

    /**
     * Add a record to an existing zone.
     */
    public function addRecord(DnsZone $zone, array $data): DnsRecord
    {
        $this->validateRecordData($data);

        $record = $zone->records()->create([
            'name' => trim($data['name']),
            'type' => strtoupper(trim($data['type'])),
            'content' => trim($data['content']),
            'ttl' => (int) ($data['ttl'] ?? 3600),
            'priority' => isset($data['priority']) && $data['priority'] !== '' ? (int) $data['priority'] : null,
            'weight' => isset($data['weight']) && $data['weight'] !== '' ? (int) $data['weight'] : null,
            'port' => isset($data['port']) && $data['port'] !== '' ? (int) $data['port'] : null,
        ]);

        $zone->incrementSerial();

        $this->auditLogger->log(
            event: 'dns.record_added',
            auditable: $record,
            newValues: $record->toArray()
        );

        return $record;
    }

    /**
     * Update an existing record in a zone.
     */
    public function updateRecord(DnsRecord $record, array $data): DnsRecord
    {
        $this->validateRecordData($data);

        $old = $record->toArray();

        $record->update([
            'name' => trim($data['name']),
            'type' => strtoupper(trim($data['type'])),
            'content' => trim($data['content']),
            'ttl' => (int) ($data['ttl'] ?? 3600),
            'priority' => isset($data['priority']) && $data['priority'] !== '' ? (int) $data['priority'] : null,
            'weight' => isset($data['weight']) && $data['weight'] !== '' ? (int) $data['weight'] : null,
            'port' => isset($data['port']) && $data['port'] !== '' ? (int) $data['port'] : null,
        ]);

        $record->zone->incrementSerial();

        $this->auditLogger->log(
            event: 'dns.record_updated',
            auditable: $record,
            oldValues: $old,
            newValues: $record->toArray()
        );

        return $record;
    }

    /**
     * Delete a DNS record.
     */
    public function deleteRecord(DnsRecord $record): void
    {
        $zone = $record->zone;
        $old = $record->toArray();

        $record->delete();
        $zone->incrementSerial();

        $this->auditLogger->log(
            event: 'dns.record_deleted',
            auditable: $zone,
            oldValues: $old
        );
    }

    /**
     * Export raw BIND zone file content.
     */
    public function exportZoneFile(DnsZone $zone): string
    {
        return $this->compiler->compile($zone);
    }

    protected function createRecordQuietly(
        DnsZone $zone,
        string $name,
        string $type,
        string $content,
        int $ttl = 3600,
        ?int $priority = null
    ): DnsRecord {
        return $zone->records()->create([
            'name' => $name,
            'type' => $type,
            'content' => $content,
            'ttl' => $ttl,
            'priority' => $priority,
        ]);
    }

    protected function validateRecordData(array $data): void
    {
        $type = strtoupper(trim($data['type'] ?? ''));
        if (!in_array($type, DnsRecord::ALLOWED_TYPES, true)) {
            throw new InvalidArgumentException("Unsupported DNS record type [{$type}].");
        }

        $content = trim($data['content'] ?? '');
        if ($content === '') {
            throw new InvalidArgumentException('Record content cannot be empty.');
        }

        if ($type === DnsRecord::TYPE_A && !filter_var($content, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            throw new InvalidArgumentException("Record content [{$content}] is not a valid IPv4 address.");
        }

        if ($type === DnsRecord::TYPE_AAAA && !filter_var($content, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            throw new InvalidArgumentException("Record content [{$content}] is not a valid IPv6 address.");
        }
    }
}
