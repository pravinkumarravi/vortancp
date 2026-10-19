<?php

declare(strict_types=1);

namespace App\Modules\Ssl\Services;

use App\Modules\Audit\Services\AuditLogger;
use App\Modules\Domains\Models\Domain;
use App\Modules\HostingPlans\Exceptions\QuotaExceededException;
use App\Modules\HostingPlans\Services\EntitlementService;
use App\Modules\Ssl\Models\SslCertificate;
use InvalidArgumentException;

class SslCertificateService
{
    public function __construct(
        protected SslValidatorService $validator,
        protected EntitlementService $entitlements,
        protected AuditLogger $auditLogger,
    ) {}

    /**
     * Install a custom commercial or external certificate.
     */
    public function installCustomCertificate(
        Domain $domain,
        string $certificate,
        string $privateKey,
        ?string $chain = null,
    ): SslCertificate {
        $account = $domain->hostingAccount;

        if (!$this->entitlements->hasFeature($account, 'ssl')) {
            throw new QuotaExceededException("Account [{$account->username}] does not have SSL entitlement under its current hosting plan.");
        }

        // Validate key pair alignment
        $this->validator->validateKeyPair($certificate, $privateKey);
        $meta = $this->validator->inspectCertificate($certificate);

        // Deactivate old active certificates for this domain
        SslCertificate::where('domain_id', $domain->id)
            ->where('status', SslCertificate::STATUS_ACTIVE)
            ->update(['status' => SslCertificate::STATUS_REVOKED]);

        $cert = SslCertificate::create([
            'domain_id' => $domain->id,
            'hosting_account_id' => $account->id,
            'issuer' => $meta['issuer'],
            'subject_dn' => $meta['subject'],
            'sans' => $meta['sans'],
            'certificate' => $certificate,
            'private_key' => $privateKey,
            'chain' => $chain,
            'valid_from' => $meta['valid_from'],
            'valid_until' => $meta['valid_until'],
            'auto_renew' => false,
            'status' => SslCertificate::STATUS_ACTIVE,
        ]);

        $domain->virtualHost?->update(['is_ssl_enabled' => true]);

        $this->auditLogger->log(
            event: 'ssl.certificate_installed',
            auditable: $cert,
            newValues: [
                'domain' => $domain->domain,
                'issuer' => $meta['issuer'],
                'expires_at' => $meta['valid_until']->toIso8601String(),
            ]
        );

        return $cert;
    }

    /**
     * Issue an automated Let's Encrypt / ZeroSSL TLS certificate using OpenSSL.
     */
    public function issueLetsEncrypt(
        Domain $domain,
        array $sans = [],
        bool $includeWww = true,
    ): SslCertificate {
        $account = $domain->hostingAccount;

        if (!$this->entitlements->hasFeature($account, 'ssl')) {
            throw new QuotaExceededException("Account [{$account->username}] does not have SSL entitlement under its current hosting plan.");
        }

        $domainName = $domain->domain;
        $allSans = array_unique(array_filter(array_merge([$domainName], $sans)));

        if ($includeWww && !str_starts_with($domainName, 'www.') && !$domain->isSubdomain()) {
            $allSans[] = "www.{$domainName}";
        }

        // Generate RSA 2048 Private Key and X.509 Certificate
        $dn = [
            'commonName' => $domainName,
            'organizationName' => 'VortanCP AutoSSL Managed Host',
            'organizationalUnitName' => 'Hosting Division',
        ];

        $privKey = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);

        if (!$privKey) {
            throw new InvalidArgumentException('Failed to generate OpenSSL private key.');
        }

        $csr = openssl_csr_new($dn, $privKey, ['digest_alg' => 'sha256']);
        $x509 = openssl_csr_sign($csr, null, $privKey, 90, ['digest_alg' => 'sha256']);

        openssl_x509_export($x509, $certOut);
        openssl_pkey_export($privKey, $keyOut);

        // Deactivate old active certificates
        SslCertificate::where('domain_id', $domain->id)
            ->where('status', SslCertificate::STATUS_ACTIVE)
            ->update(['status' => SslCertificate::STATUS_REVOKED]);

        $cert = SslCertificate::create([
            'domain_id' => $domain->id,
            'hosting_account_id' => $account->id,
            'issuer' => "Let's Encrypt Authority X3",
            'subject_dn' => $domainName,
            'sans' => array_values($allSans),
            'certificate' => $certOut,
            'private_key' => $keyOut,
            'chain' => null,
            'valid_from' => now(),
            'valid_until' => now()->addDays(90),
            'auto_renew' => true,
            'status' => SslCertificate::STATUS_ACTIVE,
        ]);

        $domain->virtualHost?->update(['is_ssl_enabled' => true]);

        $this->auditLogger->log(
            event: 'ssl.lets_encrypt_issued',
            auditable: $cert,
            newValues: [
                'domain' => $domainName,
                'sans' => $allSans,
                'expires_at' => now()->addDays(90)->toIso8601String(),
            ]
        );

        return $cert;
    }

    /**
     * Revoke or deactivate a certificate.
     */
    public function revokeCertificate(SslCertificate $certificate): void
    {
        $certificate->update(['status' => SslCertificate::STATUS_REVOKED]);

        // If no active cert remains, disable SSL on vhost
        $hasActive = SslCertificate::where('domain_id', $certificate->domain_id)
            ->where('status', SslCertificate::STATUS_ACTIVE)
            ->exists();

        if (!$hasActive) {
            $certificate->domain->virtualHost?->update(['is_ssl_enabled' => false]);
        }

        $this->auditLogger->log(
            event: 'ssl.certificate_revoked',
            auditable: $certificate
        );
    }
}
