<?php

declare(strict_types=1);

namespace Core;

use DateInterval;
use DateTime;
use Psr\SimpleCache\CacheInterface;

/**
 * PSR-16 compliant file-based cache.
 * 
 * Implements Psr\SimpleCache\CacheInterface for interoperability.
 * Also provides a static facade for convenient access.
 * 
 * Usage (Static):
 *   Cache::set('key', $value, 3600);  // Store for 1 hour
 *   Cache::get('key');                 // Retrieve
 *   Cache::remember('key', 3600, fn() => expensiveCall());
 * 
 * Usage (Instance):
 *   $cache = Cache::instance();
 *   $cache->set('key', $value, 3600);
 */
final class Cache implements CacheInterface
{
    private static ?string $path = null;
    private static ?self $instance = null;

    /**
     * Get a singleton instance for PSR-16 interface usage.
     */
    public static function instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

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
        self::validateKey($key);
        return self::getPath() . '/' . md5($key) . '.cache';
    }

    /**
     * Validate cache key per PSR-16.
     * 
     * @throws InvalidCacheKeyException If key contains reserved characters
     */
    private static function validateKey(string $key): void
    {
        if ($key === '') {
            throw new InvalidCacheKeyException('Cache key cannot be empty');
        }

        // PSR-16 reserved characters: {}()/\@:
        if (preg_match('/[{}()\\/\\\\@:]/', $key)) {
            throw new InvalidCacheKeyException(
                "Cache key '{$key}' contains reserved characters: {}()/\\@:"
            );
        }
    }

    /**
     * Convert TTL to seconds.
     * 
     * @param null|int|DateInterval $ttl Time to live
     * @return int|null Seconds, or null for forever
     */
    private static function normalizeTtl(null|int|DateInterval $ttl): ?int
    {
        if ($ttl === null) {
            return null; // Use default (forever)
        }

        if ($ttl instanceof DateInterval) {
            $now = new DateTime();
            $future = (clone $now)->add($ttl);
            return $future->getTimestamp() - $now->getTimestamp();
        }

        return $ttl;
    }

    // ─────────────────────────────────────────────────────────────
    // PSR-16 CacheInterface Methods (instance-based)
    // ─────────────────────────────────────────────────────────────

    /**
     * {@inheritdoc}
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return self::doGet($key, $default);
    }

    /**
     * {@inheritdoc}
     */
    public function set(string $key, mixed $value, null|int|DateInterval $ttl = null): bool
    {
        return self::doSet($key, $value, $ttl);
    }

    /**
     * {@inheritdoc}
     */
    public function delete(string $key): bool
    {
        return self::doDelete($key);
    }

    /**
     * {@inheritdoc}
     */
    public function clear(): bool
    {
        return self::doClear();
    }

    /**
     * {@inheritdoc}
     */
    public function has(string $key): bool
    {
        return self::doHas($key);
    }

    /**
     * {@inheritdoc}
     * 
     * @param iterable<string> $keys
     * @return iterable<string, mixed>
     */
    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = self::doGet($key, $default);
        }
        return $result;
    }

    /**
     * {@inheritdoc}
     * 
     * @param iterable<string, mixed> $values
     */
    public function setMultiple(iterable $values, null|int|DateInterval $ttl = null): bool
    {
        $success = true;
        foreach ($values as $key => $value) {
            if (!self::doSet($key, $value, $ttl)) {
                $success = false;
            }
        }
        return $success;
    }

    /**
     * {@inheritdoc}
     * 
     * @param iterable<string> $keys
     */
    public function deleteMultiple(iterable $keys): bool
    {
        $success = true;
        foreach ($keys as $key) {
            if (!self::doDelete($key)) {
                $success = false;
            }
        }
        return $success;
    }

    // ─────────────────────────────────────────────────────────────
    // Static Facade Methods (for convenience)
    // ─────────────────────────────────────────────────────────────

    /**
     * Store a value in the cache (static facade).
     * 
     * PSR-16 alias for put().
     */
    public static function put(string $key, mixed $value, null|int|DateInterval $ttl = null): bool
    {
        return self::doSet($key, $value, $ttl);
    }

    /**
     * Get a value from the cache (static facade).
     */
    public static function pull(string $key, mixed $default = null): mixed
    {
        return self::doGet($key, $default);
    }

    /**
     * Check if a key exists in the cache (static facade).
     */
    public static function exists(string $key): bool
    {
        return self::doHas($key);
    }

    /**
     * Remove a value from the cache (static facade).
     * 
     * Legacy alias for delete().
     */
    public static function forget(string $key): bool
    {
        return self::doDelete($key);
    }

    /**
     * Clear all cached values (static facade).
     * 
     * Legacy alias for clear().
     */
    public static function flush(): bool
    {
        return self::doClear();
    }

    /**
     * Get or store a value (compute if missing).
     * 
     * Usage:
     *   Cache::remember('users', 3600, fn() => DB::table('users')->get());
     */
    public static function remember(string $key, null|int|DateInterval $ttl, callable $callback): mixed
    {
        $value = self::doGet($key);

        if ($value !== null) {
            return $value;
        }

        $value = $callback();
        self::doSet($key, $value, $ttl);

        return $value;
    }

    /**
     * Store a value forever (no expiration).
     */
    public static function forever(string $key, mixed $value): bool
    {
        return self::doSet($key, $value, null);
    }

    /**
     * Increment a cached value.
     */
    public static function increment(string $key, int $amount = 1): int
    {
        $value = (int) self::doGet($key, 0) + $amount;
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
    // Internal Implementation
    // ─────────────────────────────────────────────────────────────

    /**
     * Internal get implementation.
     */
    private static function doGet(string $key, mixed $default = null): mixed
    {
        $file = self::filePath($key);

        if (!file_exists($file)) {
            return $default;
        }

        $contents = file_get_contents($file);
        if ($contents === false) {
            return $default;
        }

        $data = @unserialize($contents);
        if ($data === false) {
            return $default;
        }

        // Check expiration
        if (isset($data['expires']) && $data['expires'] > 0 && $data['expires'] < time()) {
            self::doDelete($key);
            return $default;
        }

        // Use array_key_exists to properly handle null values
        return array_key_exists('value', $data) ? $data['value'] : $default;
    }

    /**
     * Internal set implementation.
     */
    private static function doSet(string $key, mixed $value, null|int|DateInterval $ttl): bool
    {
        $seconds = self::normalizeTtl($ttl);

        // PSR-16: TTL of 0 or negative means delete
        if ($seconds !== null && $seconds <= 0) {
            return self::doDelete($key);
        }

        $data = [
            'value' => $value,
            'expires' => $seconds !== null ? time() + $seconds : 0,
        ];

        $result = file_put_contents(
            self::filePath($key),
            serialize($data),
            LOCK_EX
        );

        return $result !== false;
    }

    /**
     * Internal delete implementation.
     */
    private static function doDelete(string $key): bool
    {
        $file = self::filePath($key);

        if (!file_exists($file)) {
            return true; // PSR-16: non-existent key is considered success
        }

        return @unlink($file);
    }

    /**
     * Internal clear implementation.
     */
    private static function doClear(): bool
    {
        $files = glob(self::getPath() . '/*.cache');

        if ($files === false) {
            return false;
        }

        $success = true;
        foreach ($files as $file) {
            if (!@unlink($file)) {
                $success = false;
            }
        }

        return $success;
    }

    /**
     * Internal has implementation.
     */
    private static function doHas(string $key): bool
    {
        return self::doGet($key, '__CACHE_MISS__') !== '__CACHE_MISS__';
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
        self::$instance = null;
    }

    /**
     * Set a custom cache path (for testing).
     */
    public static function setPath(?string $path): void
    {
        self::$path = $path;
    }
}
