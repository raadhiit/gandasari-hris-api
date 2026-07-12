<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateDaidanApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = $request->bearerToken();

        if ($apiKey === null) {
            return $this->unauthorizedResponse(
                'API key is required.'
            );
        }

        $expectedHash = config('services.daidan.api_key_hash');

        if (! is_string($expectedHash) || $expectedHash === '') {
            return response()->json([
                'success' => false,
                'message' => 'API authentication is not configured.',
            ], 500);
        }

        $providedHash = hash('sha256', $apiKey);

        if (! hash_equals($expectedHash, $providedHash)) {
            return $this->unauthorizedResponse(
                'Invalid API key.'
            );
        }

        return $next($request);
    }

    private function unauthorizedResponse(string $message): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
        ], 401);
    }
}