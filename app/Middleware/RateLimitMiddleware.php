<?php

declare(strict_types=1);

namespace App\Middleware;

use Core\Cache;
use Core\Middleware;
use Core\Request;
use Core\Response;

/**
 * Rate Limiting Middleware
 * 
 * Limits requests per IP address using the Cache system.
 * 
 * Usage:
 *   Route::post('/api/login', $handler)->middleware(RateLimitMiddleware::class);
 *   
 *   // With custom limits (via constructor in route definition)
 *   Route::post('/api/login', $handler)->middleware(new RateLimitMiddleware(5, 60));
 */
class RateLimitMiddleware implements Middleware
{
    /**
     * Default: 60 requests per minute.
     */
    private int $maxAttempts;
    private int $decaySeconds;

    public function __construct(int $maxAttempts = 60, int $decaySeconds = 60)
    {
        $this->maxAttempts = $maxAttempts;
        $this->decaySeconds = $decaySeconds;
    }

    /**
     * Handle the request.
     */
    public function handle(Request $request, callable $next): Response
    {
        $key = $this->resolveRequestKey($request);
        
        $attempts = (int) Cache::get($key, 0);

        if ($attempts >= $this->maxAttempts) {
            return $this->buildTooManyRequestsResponse($request);
        }

        // Increment attempts
        Cache::put($key, $attempts + 1, $this->decaySeconds);

        // Continue with request
        $response = $next($request);

        // Add rate limit headers
        return $this->addRateLimitHeaders($response, $attempts + 1);
    }

    /**
     * Generate a unique cache key for the request.
     */
    private function resolveRequestKey(Request $request): string
    {
        $ip = $request->server['REMOTE_ADDR'] ?? '127.0.0.1';
        $path = $request->path;
        
        return 'rate_limit:' . md5($ip . '|' . $path);
    }

    /**
     * Build a 429 Too Many Requests response.
     */
    private function buildTooManyRequestsResponse(Request $request): Response
    {
        $response = new Response();

        if ($request->wantsJson()) {
            return $response->json([
                'error' => 'Too Many Requests',
                'message' => 'Rate limit exceeded. Please try again later.',
                'retry_after' => $this->decaySeconds,
            ], 429);
        }

        return $response
            ->status(429)
            ->header('Retry-After', (string) $this->decaySeconds)
            ->html('<h1>429 Too Many Requests</h1><p>Please try again later.</p>');
    }

    /**
     * Add rate limit headers to response.
     */
    private function addRateLimitHeaders(Response $response, int $currentAttempts): Response
    {
        return $response
            ->header('X-RateLimit-Limit', (string) $this->maxAttempts)
            ->header('X-RateLimit-Remaining', (string) max(0, $this->maxAttempts - $currentAttempts));
    }
}
