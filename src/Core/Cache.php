<?php

declare(strict_types=1);

namespace Core;

/**
 * Simple file-based cache.
 * 
 * Usage:
 *   Cache::put('key', $value, 3600);  // Store for 1 hour
 *   Cache::get('key');                 // Retrieve
 *   Cache::remember('key', 3600, fn() => expensiveCall());
 */
final class Cache
{
    private static ?string $path = null;

    /**
     * Get the cache directory path.
     */
    private static function getPath(): string
    {
        if (self::$path === null) {
            self::$path = Config::get('path.cache', BASE_PATH . '/storage/cache');
        }

        if (!is_dir(self::$path)) {
            mkdir(self::$path, 0755, true);
        }

        return self::$path;
    }

    /**
     * Get the file path for a cache key.
     */
    private static function filePath(string $key): string
    {
        return self::getPath() . '/' . md5($key) . '.cache';
    }

    /**
     * Store a value in the cache.
     * 
     * @param string $key Cache key
     * @param mixed $value Value to store
     * @param int $ttl Time to live in seconds (0 = forever)
     */
    public static function put(string $key, mixed $value, int $ttl = 0): void
    {
        $data = [
            'value' => $value,
            'expires' => $ttl > 0 ? time() + $ttl : 0,
        ];

        file_put_contents(
            self::filePath($key),
            serialize($data),
            LOCK_EX
        );
    }

    /**
     * Get a value from the cache.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $file = self::filePath($key);

        if (!file_exists($file)) {
            return $default;
        }

        $data = unserialize(file_get_contents($file));

        // Check expiration
        if ($data['expires'] > 0 && $data['expires'] < time()) {
            self::forget($key);
            return $default;
        }

        return $data['value'];
    }

    /**
     * Check if a key exists in the cache.
     */
    public static function has(string $key): bool
    {
        return self::get($key, '__CACHE_MISS__') !== '__CACHE_MISS__';
    }

    /**
     * Remove a value from the cache.
     */
    public static function forget(string $key): void
    {
        $file = self::filePath($key);

        if (file_exists($file)) {
            unlink($file);
        }
    }

    /**
     * Clear all cached values.
     */
    public static function flush(): void
    {
        $files = glob(self::getPath() . '/*.cache');

        foreach ($files as $file) {
            unlink($file);
        }
    }

    /**
     * Get or store a value (compute if missing).
     * 
     * Usage:
     *   Cache::remember('users', 3600, fn() => DB::table('users')->get());
     */
    public static function remember(string $key, int $ttl, callable $callback): mixed
    {
        $value = self::get($key);

        if ($value !== null) {
            return $value;
        }

        $value = $callback();
        self::put($key, $value, $ttl);

        return $value;
    }

    /**
     * Store a value forever (no expiration).
     */
    public static function forever(string $key, mixed $value): void
    {
        self::put($key, $value, 0);
    }

    /**
     * Increment a cached value.
     */
    public static function increment(string $key, int $amount = 1): int
    {
        $value = (int) self::get($key, 0) + $amount;
        self::forever($key, $value);
        return $value;
    }

    /**
     * Decrement a cached value.
     */
    public static function decrement(string $key, int $amount = 1): int
    {
        return self::increment($key, -$amount);
    }

    // ─────────────────────────────────────────────────────────────
    // Testing Support
    // ─────────────────────────────────────────────────────────────

    /**
     * Reset all static state (for testing or long-running processes).
     */
    public static function reset(): void
    {
        self::$path = null;
    }

    /**
     * Set a custom cache path (for testing).
     */
    public static function setPath(?string $path): void
    {
        self::$path = $path;
    }
}
