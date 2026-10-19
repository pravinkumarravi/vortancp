<?php

declare(strict_types=1);

namespace App\Modules\Ssl\Services;

use Carbon\Carbon;
use InvalidArgumentException;

class SslValidatorService
{
    /**
     * Parse and inspect an X.509 certificate string.
     *
     * @return array{
     *     subject: string,
     *     issuer: string,
     *     valid_from: Carbon,
     *     valid_until: Carbon,
     *     sans: array<string>,
     *     is_valid_dates: bool
     * }
     */
    public function inspectCertificate(string $certificate): array
    {
        $parsed = openssl_x509_parse($certificate);

        if ($parsed === false) {
            throw new InvalidArgumentException('Failed to parse X.509 certificate. Ensure valid PEM-encoded certificate content.');
        }

        $validFrom = Carbon::createFromTimestamp($parsed['validFrom_time_t']);
        $validUntil = Carbon::createFromTimestamp($parsed['validTo_time_t']);

        // Extract SANs (Subject Alternative Names)
        $sans = [];
        if (isset($parsed['extensions']['subjectAltName'])) {
            $rawSans = explode(',', $parsed['extensions']['subjectAltName']);
            foreach ($rawSans as $item) {
                $item = trim($item);
                if (str_starts_with($item, 'DNS:')) {
                    $sans[] = substr($item, 4);
                }
            }
        }

        $commonName = $parsed['subject']['CN'] ?? ($parsed['name'] ?? 'Unknown');

        return [
            'subject' => $commonName,
            'issuer' => $parsed['issuer']['O'] ?? ($parsed['issuer']['CN'] ?? 'Unknown Authority'),
            'valid_from' => $validFrom,
            'valid_until' => $validUntil,
            'sans' => $sans,
            'is_valid_dates' => now()->between($validFrom, $validUntil),
        ];
    }

    /**
     * Validate that a certificate matches a private key.
     */
    public function validateKeyPair(string $certificate, string $privateKey): bool
    {
        $res = openssl_x509_check_private_key($certificate, $privateKey);

        if (!$res) {
            throw new InvalidArgumentException('The provided certificate does not match the private key.');
        }

        return true;
    }
}
