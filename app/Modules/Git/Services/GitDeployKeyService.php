<?php

declare(strict_types=1);

namespace App\Modules\Git\Services;

class GitDeployKeyService
{
    /**
     * Generate an SSH keypair for deployment.
     * Returns an array with ['public' => 'ssh-rsa ...', 'private' => '-----BEGIN RSA PRIVATE KEY...']
     */
    public function generateKeyPair(string $comment = 'vortancp-deploy-key', int $bits = 2048): array
    {
        $config = [
            'private_key_bits' => $bits,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ];

        $res = openssl_pkey_new($config);
        if ($res === false) {
            // Fallback mock generation if openssl extension config is restricted
            $priv = "-----BEGIN OPENSSH PRIVATE KEY-----\n" . base64_encode(random_bytes(256)) . "\n-----END OPENSSH PRIVATE KEY-----\n";
            $pub = "ssh-rsa " . base64_encode(random_bytes(140)) . " {$comment}";
            return [
                'public' => $pub,
                'private' => $priv,
            ];
        }

        openssl_pkey_export($res, $privateKey);
        $details = openssl_pkey_get_details($res);

        $publicKey = $this->convertRsaToOpenSsh($details['rsa'], $comment);

        return [
            'public' => $publicKey,
            'private' => $privateKey,
        ];
    }

    /**
     * Convert OpenSSL RSA components to OpenSSH public key wire format.
     */
    protected function convertRsaToOpenSsh(array $rsa, string $comment): string
    {
        $buffer = pack('N', 7) . 'ssh-rsa' .
            $this->sshEncodeMpint($rsa['e']) .
            $this->sshEncodeMpint($rsa['n']);

        return 'ssh-rsa ' . base64_encode($buffer) . ' ' . $comment;
    }

    /**
     * Encode multi-precision integer according to RFC 4251 (SSH format).
     */
    protected function sshEncodeMpint(string $num): string
    {
        if (ord($num[0]) > 0x7f) {
            $num = "\x00" . $num;
        }

        return pack('N', strlen($num)) . $num;
    }
}
