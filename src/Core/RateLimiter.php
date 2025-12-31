<?php

declare(strict_types=1);

namespace Core;

/**
 * Simple rate limiter using file-based storage.
 * 
 * No Redis required - uses cache directory for storage.
 * 
 * Usage as middleware:
 *   $router->post('/login', $handler)->middleware(RateLimiter::perMinute(5));
 *   $router->post('/api/send', $handler)->middleware(RateLimiter::perHour(100));
 * 
 * Usage in code:
 *   if (RateLimiter::tooManyAttempts('login:' . $ip, 5)) {
 *       return response()->json(['error' => 'Too many attempts'], 429);
 *   }
 *   RateLimiter::hit('login:' . $ip, 60);
 */
final class RateLimiter
{
    private static ?string $storagePath = null;

    /**
     * Create middleware that limits requests per minute.
     * 
     * Usage:
     *   ->middleware(RateLimiter::perMinute(10))
     */
    public static function perMinute(int $maxAttempts): callable
    {
        return self::middleware($maxAttempts, 60);
    }

    /**
     * Create middleware that limits requests per hour.
     */
    public static function perHour(int $maxAttempts): callable
    {
        return self::middleware($maxAttempts, 3600);
    }

    /**
     * Create middleware that limits requests per day.
     */
    public static function perDay(int $maxAttempts): callable
    {
        return self::middleware($maxAttempts, 86400);
    }

    /**
     * Create rate limiting middleware.
     * 
     * @param int $maxAttempts Maximum attempts allowed
     * @param int $decaySeconds Time window in seconds
     */
    public static function middleware(int $maxAttempts, int $decaySeconds): callable
    {
        return function (Request $request, Response $response, callable $next) use ($maxAttempts, $decaySeconds) {
            $key = self::resolveKey($request);
            
            if (self::tooManyAttempts($key, $maxAttempts)) {
                $retryAfter = self::availableIn($key);
                
                return $response
                    ->header('Retry-After', (string) $retryAfter)
                    ->header('X-RateLimit-Limit', (string) $maxAttempts)
                    ->header('X-RateLimit-Remaining', '0')
                    ->json([
                        'error' => 'Too many requests',
                        'retry_after' => $retryAfter,
                    ], 429);
            }
            
            self::hit($key, $decaySeconds);
            
            $remaining = max(0, $maxAttempts - self::attempts($key));
            $response->header('X-RateLimit-Limit', (string) $maxAttempts);
            $response->header('X-RateLimit-Remaining', (string) $remaining);
            
            return $next($request, $response);
        };
    }

    /**
     * Check if too many attempts have been made.
     */
    public static function tooManyAttempts(string $key, int $maxAttempts): bool
    {
        return self::attempts($key) >= $maxAttempts;
    }

    /**
     * Get the number of attempts for a key.
     */
    public static function attempts(string $key): int
    {
        $data = self::get($key);
        
        if ($data === null) {
            return 0;
        }
        
        // Check if expired
        if ($data['expires'] < time()) {
            self::forget($key);
            return 0;
        }
        
        return $data['attempts'];
    }

    /**
     * Increment attempts for a key.
     */
    public static function hit(string $key, int $decaySeconds = 60): int
    {
        $data = self::get($key);
        
        if ($data === null || $data['expires'] < time()) {
            // Start fresh
            $data = [
                'attempts' => 1,
                'expires' => time() + $decaySeconds,
            ];
        } else {
            // Increment
            $data['attempts']++;
        }
        
        self::put($key, $data);
        
        return $data['attempts'];
    }

    /**
     * Get seconds until rate limit resets.
     */
    public static function availableIn(string $key): int
    {
        $data = self::get($key);
        
        if ($data === null) {
            return 0;
        }
        
        return max(0, $data['expires'] - time());
    }

    /**
     * Clear attempts for a key.
     */
    public static function clear(string $key): void
    {
        self::forget($key);
    }

    /**
     * Resolve the rate limit key from request.
     */
    private static function resolveKey(Request $request): string
    {
        // Use IP + path as key
        $ip = $request->ip();
        $path = $request->path;
        
        return 'ratelimit:' . md5($ip . '|' . $path);
    }

    // ─────────────────────────────────────────────────────────────
    // Storage (File-based)
    // ─────────────────────────────────────────────────────────────

    private static function getStoragePath(): string
    {
        if (self::$storagePath === null) {
            self::$storagePath = (defined('BASE_PATH') ? BASE_PATH : getcwd()) 
                . '/storage/cache/ratelimit';
        }
        
        if (!is_dir(self::$storagePath)) {
            mkdir(self::$storagePath, 0755, true);
        }
        
        return self::$storagePath;
    }

    private static function filePath(string $key): string
    {
        return self::getStoragePath() . '/' . md5($key) . '.json';
    }

    private static function get(string $key): ?array
    {
        $file = self::filePath($key);
        
        if (!file_exists($file)) {
            return null;
        }
        
        $content = file_get_contents($file);
        return $content ? json_decode($content, true) : null;
    }

    private static function put(string $key, array $data): void
    {
        file_put_contents(
            self::filePath($key),
            json_encode($data),
            LOCK_EX
        );
    }

    private static function forget(string $key): void
    {
        $file = self::filePath($key);
        
        if (file_exists($file)) {
            unlink($file);
        }
    }

    // ─────────────────────────────────────────────────────────────
    // Testing Support
    // ─────────────────────────────────────────────────────────────

    /**
     * Reset all static state.
     */
    public static function reset(): void
    {
        self::$storagePath = null;
    }

    /**
     * Set custom storage path (for testing).
     */
    public static function setStoragePath(?string $path): void
    {
        self::$storagePath = $path;
    }

    /**
     * Flush all rate limit data.
     */
    public static function flush(): void
    {
        $files = glob(self::getStoragePath() . '/*.json');
        
        foreach ($files ?: [] as $file) {
            unlink($file);
        }
    }
}
