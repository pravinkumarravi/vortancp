<?php

declare(strict_types=1);

namespace App\Modules\Billing\Http\Middleware;

use App\Modules\Billing\Models\ApiKey;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateApiKey
{
    /**
     * Handle an incoming request and validate API key credentials.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ?string $requiredAbility = null): Response
    {
        $token = $request->header('X-API-Key');

        if (empty($token)) {
            $bearer = $request->bearerToken();
            if (!empty($bearer)) {
                $token = $bearer;
            }
        }

        if (empty($token)) {
            $token = $request->query('api_key');
        }

        if (empty($token) || !is_string($token)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Authentication required. Provide an API key via X-API-Key header or Bearer authorization token.',
            ], 401);
        }

        $apiKey = ApiKey::validateToken($token);

        if ($apiKey === null) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid, revoked, or expired API Key.',
            ], 401);
        }

        if ($requiredAbility !== null && !$apiKey->can($requiredAbility)) {
            return response()->json([
                'status' => 'error',
                'message' => "Insufficient permissions for capability [{$requiredAbility}].",
            ], 403);
        }

        $request->attributes->set('api_key', $apiKey);

        return $next($request);
    }
}
