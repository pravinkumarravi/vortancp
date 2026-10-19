<?php

declare(strict_types=1);

namespace App\Modules\Email\Services;

use App\Modules\Audit\Services\AuditLogger;
use App\Modules\Dns\Models\DnsRecord;
use App\Modules\Dns\Models\DnsZone;
use App\Modules\Domains\Models\Domain;
use App\Modules\Email\Models\MailAlias;
use App\Modules\Email\Models\MailDomain;
use App\Modules\Email\Models\Mailbox;
use App\Modules\HostingPlans\Services\EntitlementService;
use Illuminate\Support\Facades\Hash;
use InvalidArgumentException;

class MailboxManagerService
{
    public function __construct(
        protected EntitlementService $entitlements,
        protected AuditLogger $auditLogger,
    ) {}

    /**
     * Ensure a MailDomain exists for a domain, generating DKIM keypair if missing.
     */
    public function ensureMailDomain(Domain $domain): MailDomain
    {
        $existing = MailDomain::where('domain_id', $domain->id)->first();
        if ($existing) {
            return $existing;
        }

        // Generate RSA 2048 DKIM Keypair
        $privKey = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);

        $privKeyPem = '';
        $pubKeyPem = '';
        if ($privKey) {
            openssl_pkey_export($privKey, $privKeyPem);
            $pubDetails = openssl_pkey_get_details($privKey);
            $pubKeyPem = $pubDetails['key'] ?? '';
        }

        // Clean public key for DNS TXT format
        $cleanedPub = preg_replace('/-----(BEGIN|END) PUBLIC KEY-----|\r|\n/', '', $pubKeyPem);

        $mailDomain = MailDomain::create([
            'domain_id' => $domain->id,
            'hosting_account_id' => $domain->hosting_account_id,
            'name' => $domain->domain,
            'dkim_selector' => 'default',
            'dkim_private_key' => $privKeyPem,
            'dkim_public_key' => $cleanedPub,
            'status' => MailDomain::STATUS_ACTIVE,
        ]);

        // If DNS zone exists, attach DKIM TXT record
        $zone = DnsZone::where('name', $domain->domain)->first();
        if ($zone && $cleanedPub) {
            $zone->records()->create([
                'name' => 'default._domainkey',
                'type' => DnsRecord::TYPE_TXT,
                'content' => "v=DKIM1; k=rsa; p={$cleanedPub}",
                'ttl' => 3600,
            ]);
            $zone->incrementSerial();
        }

        $this->auditLogger->log(
            event: 'mail_domain.created',
            auditable: $mailDomain,
            newValues: ['domain' => $domain->domain]
        );

        return $mailDomain;
    }

    /**
     * Create a virtual mailbox.
     */
    public function createMailbox(
        MailDomain $mailDomain,
        string $localPart,
        string $password,
        int $quotaMb = 1024,
    ): Mailbox {
        $cleanLocal = strtolower(trim($localPart));

        if (!preg_match('/^[a-z0-9._-]+$/', $cleanLocal)) {
            throw new InvalidArgumentException("Invalid local part [{$cleanLocal}]. Only letters, numbers, dots, and hyphens allowed.");
        }

        $email = "{$cleanLocal}@{$mailDomain->name}";

        if (Mailbox::where('email', $email)->exists()) {
            throw new InvalidArgumentException("Mailbox [{$email}] already exists.");
        }

        if (strlen($password) < 8) {
            throw new InvalidArgumentException('Mailbox password must be at least 8 characters.');
        }

        $account = $mailDomain->hostingAccount;
        $currentCount = Mailbox::where('hosting_account_id', $account->id)->count();

        // Enforce quota via EntitlementService
        $this->entitlements->assertCan($account, 'mailbox', $currentCount);

        $mailbox = Mailbox::create([
            'mail_domain_id' => $mailDomain->id,
            'hosting_account_id' => $account->id,
            'email' => $email,
            'local_part' => $cleanLocal,
            'password_hash' => Hash::make($password),
            'quota_mb' => max(100, $quotaMb),
            'used_mb' => 0,
            'status' => Mailbox::STATUS_ACTIVE,
        ]);

        $this->auditLogger->log(
            event: 'mailbox.created',
            auditable: $mailbox,
            newValues: ['email' => $email, 'quota_mb' => $quotaMb]
        );

        return $mailbox;
    }

    /**
     * Create an email forwarder / alias.
     */
    public function createAlias(MailDomain $mailDomain, string $sourceEmail, string $destinationEmail): MailAlias
    {
        $cleanSource = strtolower(trim($sourceEmail));
        $cleanDest = strtolower(trim($destinationEmail));

        if (!filter_var($cleanDest, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException("Destination email [{$cleanDest}] is invalid.");
        }

        $alias = MailAlias::create([
            'mail_domain_id' => $mailDomain->id,
            'source_email' => $cleanSource,
            'destination_email' => $cleanDest,
            'is_active' => true,
        ]);

        $this->auditLogger->log(
            event: 'mail_alias.created',
            auditable: $alias,
            newValues: ['source' => $cleanSource, 'destination' => $cleanDest]
        );

        return $alias;
    }

    /**
     * Change mailbox password.
     */
    public function changePassword(Mailbox $mailbox, string $newPassword): void
    {
        if (strlen($newPassword) < 8) {
            throw new InvalidArgumentException('Password must be at least 8 characters.');
        }

        $mailbox->update(['password_hash' => Hash::make($newPassword)]);

        $this->auditLogger->log(
            event: 'mailbox.password_changed',
            auditable: $mailbox
        );
    }

    /**
     * Delete mailbox.
     */
    public function deleteMailbox(Mailbox $mailbox): void
    {
        $email = $mailbox->email;
        $this->auditLogger->log(
            event: 'mailbox.deleted',
            auditable: $mailbox,
            oldValues: ['email' => $email]
        );

        $mailbox->delete();
    }

    /**
     * Delete alias.
     */
    public function deleteAlias(MailAlias $alias): void
    {
        $alias->delete();
    }
}
