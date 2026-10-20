<?php

declare(strict_types=1);

namespace App\Modules\Agent\Services;

use InvalidArgumentException;

class AgentProtocolService
{
    public const CODE_PARSE_ERROR = -32700;
    public const CODE_INVALID_REQUEST = -32600;
    public const CODE_METHOD_NOT_FOUND = -32601;
    public const CODE_INVALID_PARAMS = -32602;
    public const CODE_INTERNAL_ERROR = -32603;
    public const CODE_AUTH_ERROR = -32001;

    /**
     * Build standard JSON-RPC 2.0 request payload with HMAC authentication.
     */
    public function formatRequest(string $method, array $params, string $secret, ?string $id = null): array
    {
        $id = $id ?? bin2hex(random_bytes(8));
        $timestamp = time();

        $payload = [
            'jsonrpc' => '2.0',
            'method' => $method,
            'params' => $params,
            'id' => $id,
            'auth' => [
                'timestamp' => $timestamp,
                'signature' => $this->sign($method, $params, $timestamp, $secret),
            ],
        ];

        return $payload;
    }

    /**
     * Validate an incoming JSON-RPC 2.0 request and its HMAC authentication.
     */
    public function validateRequest(array $data, string $secret, int $maxDriftSeconds = 60): void
    {
        if (! isset($data['jsonrpc']) || $data['jsonrpc'] !== '2.0') {
            throw new InvalidArgumentException("Missing or invalid JSON-RPC 2.0 protocol version.", self::CODE_INVALID_REQUEST);
        }

        if (empty($data['method']) || ! is_string($data['method'])) {
            throw new InvalidArgumentException("Missing or invalid RPC method name.", self::CODE_INVALID_REQUEST);
        }

        if (! array_key_exists('id', $data)) {
            throw new InvalidArgumentException("Missing request ID.", self::CODE_INVALID_REQUEST);
        }

        if (! isset($data['auth']) || ! is_array($data['auth'])) {
            throw new InvalidArgumentException("Missing authentication metadata.", self::CODE_AUTH_ERROR);
        }

        $timestamp = (int) ($data['auth']['timestamp'] ?? 0);
        $signature = (string) ($data['auth']['signature'] ?? '');

        if (abs(time() - $timestamp) > $maxDriftSeconds) {
            throw new InvalidArgumentException("Authentication timestamp expired or drifted beyond {$maxDriftSeconds}s threshold.", self::CODE_AUTH_ERROR);
        }

        $expectedSig = $this->sign($data['method'], $data['params'] ?? [], $timestamp, $secret);
        if (! hash_equals($expectedSig, $signature)) {
            throw new InvalidArgumentException("Invalid HMAC cryptographic signature.", self::CODE_AUTH_ERROR);
        }
    }

    /**
     * Compute HMAC-SHA256 signature for method + params + timestamp.
     */
    public function sign(string $method, array $params, int $timestamp, string $secret): string
    {
        $normalizedParams = json_encode($params, JSON_UNESCAPED_SLASHES);
        $message = "{$method}|{$normalizedParams}|{$timestamp}";
        return hash_hmac('sha256', $message, $secret);
    }

    /**
     * Format a successful JSON-RPC 2.0 response.
     */
    public function formatSuccessResponse($id, mixed $result): array
    {
        return [
            'jsonrpc' => '2.0',
            'result' => $result,
            'id' => $id,
        ];
    }

    /**
     * Format an error JSON-RPC 2.0 response.
     */
    public function formatErrorResponse($id, int $code, string $message, mixed $data = null): array
    {
        return [
            'jsonrpc' => '2.0',
            'error' => [
                'code' => $code,
                'message' => $message,
                'data' => $data,
            ],
            'id' => $id,
        ];
    }
}
