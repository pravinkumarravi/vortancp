<?php

declare(strict_types=1);

namespace Tests\Unit\Ssl;

use App\Modules\Ssl\Services\SslValidatorService;
use InvalidArgumentException;
use Tests\TestCase;

class SslValidatorTest extends TestCase
{
    private SslValidatorService $validator;
    private string $certPem;
    private string $keyPem;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = new SslValidatorService();

        // Generate test key and self-signed certificate
        $privKey = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);

        $dn = [
            'commonName' => 'ssl-test.vortan.local',
            'organizationName' => 'Vortan Security Unit',
        ];

        $csr = openssl_csr_new($dn, $privKey, ['digest_alg' => 'sha256']);
        $x509 = openssl_csr_sign($csr, null, $privKey, 365, ['digest_alg' => 'sha256']);

        openssl_x509_export($x509, $certOut);
        openssl_pkey_export($privKey, $keyOut);
        $this->certPem = $certOut;
        $this->keyPem = $keyOut;
    }

    public function test_can_inspect_valid_x509_certificate(): void
    {
        $meta = $this->validator->inspectCertificate($this->certPem);

        $this->assertSame('ssl-test.vortan.local', $meta['subject']);
        $this->assertSame('Vortan Security Unit', $meta['issuer']);
        $this->assertTrue($meta['is_valid_dates']);
    }

    public function test_validates_matching_key_pair(): void
    {
        $this->assertTrue($this->validator->validateKeyPair($this->certPem, $this->keyPem));
    }

    public function test_throws_exception_on_mismatched_key(): void
    {
        $otherKey = openssl_pkey_new(['private_key_bits' => 2048]);
        $otherKeyPem = '';
        openssl_pkey_export($otherKey, $otherKeyPem);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/does not match the private key/');

        $this->validator->validateKeyPair($this->certPem, $otherKeyPem);
    }
}
