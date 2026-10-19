<?php

declare(strict_types=1);

namespace App\Modules\Identity\Services;

class TotpService
{
    protected const BASE32_ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    /**
     * Generate a cryptographically secure Base32 secret string (160 bits / 20 bytes).
     */
    public function generateSecret(int $length = 32): string
    {
        $secret = '';
        $randomBytes = random_bytes($length);
        for ($i = 0; $i < $length; $i++) {
            $secret .= self::BASE32_ALPHABET[ord($randomBytes[$i]) % 32];
        }
        return $secret;
    }

    /**
     * Generate the otpauth:// URI suitable for QR codes (Google Authenticator, Authy, etc.).
     */
    public function getOtpAuthUrl(string $company, string $accountName, string $secret): string
    {
        $issuer = rawurlencode($company);
        $account = rawurlencode($accountName);
        return "otpauth://totp/{$issuer}:{$account}?secret={$secret}&issuer={$issuer}&algorithm=SHA1&digits=6&period=30";
    }

    /**
     * Calculate the current 6-digit TOTP code for the given secret at a timestamp.
     */
    public function calculateCode(string $secret, ?int $timestamp = null): string
    {
        $timestamp = $timestamp ?? time();
        $timeSlice = (int) floor($timestamp / 30);

        $secretBinary = $this->base32Decode($secret);

        // Pack timeSlice into an 8-byte big-endian binary string
        $timePacked = pack('N*', 0) . pack('N*', $timeSlice);

        // Compute HMAC-SHA1
        $hash = hash_hmac('sha1', $timePacked, $secretBinary, true);

        // Dynamic truncation (RFC 4226 section 5.4)
        $offset = ord(substr($hash, -1)) & 0x0F;
        $unpacked = unpack('N', substr($hash, $offset, 4));
        $code = ($unpacked[1] & 0x7FFFFFFF) % 1000000;

        return str_pad((string) $code, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Verify a submitted code against the secret within a tolerance window (default ±1 window = ±30s).
     */
    public function verifyCode(string $secret, string $submittedCode, int $window = 1): bool
    {
        $currentTime = time();
        $submittedCode = trim($submittedCode);

        for ($i = -$window; $i <= $window; $i++) {
            $checkTime = $currentTime + ($i * 30);
            if (hash_equals($this->calculateCode($secret, $checkTime), $submittedCode)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Generate an array of 8 random alphanumeric recovery codes.
     *
     * @return array<string>
     */
    public function generateRecoveryCodes(int $count = 8): array
    {
        $codes = [];
        for ($i = 0; $i < $count; $i++) {
            $codes[] = strtoupper(bin2hex(random_bytes(4)) . '-' . bin2hex(random_bytes(4)));
        }
        return $codes;
    }

    /**
     * Decode a Base32 encoded string into raw binary.
     */
    protected function base32Decode(string $base32): string
    {
        $base32 = strtoupper(trim($base32));
        $buffer = 0;
        $bitsLeft = 0;
        $binary = '';

        for ($i = 0; $i < strlen($base32); $i++) {
            $char = $base32[$i];
            if ($char === '=' || $char === ' ') {
                continue;
            }

            $val = strpos(self::BASE32_ALPHABET, $char);
            if ($val === false) {
                continue;
            }

            $buffer = ($buffer << 5) | $val;
            $bitsLeft += 5;

            if ($bitsLeft >= 8) {
                $bitsLeft -= 8;
                $binary .= chr(($buffer >> $bitsLeft) & 0xFF);
            }
        }

        return $binary;
    }
}
