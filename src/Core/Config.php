<?php

declare(strict_types=1);

namespace Core;

/**
 * Configuration loader.
 * 
 * Flat key-value access. No magic, no nesting.
 */
final class Config
{
    /** @var array<string, mixed> */
    private static array $config = [];
    private static bool $loaded = false;

    /**
     * Load configuration from file.
     */
    public static function load(string $path): void
    {
        if (self::$loaded) {
            return;
        }

        if (!file_exists($path)) {
            throw new \RuntimeException("Config file not found: {$path}");
        }

        self::$config = require $path;
        self::$loaded = true;
    }

    /**
     * Get a configuration value.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return self::$config[$key] ?? $default;
    }

    /**
     * Set a configuration value at runtime.
     */
    public static function set(string $key, mixed $value): void
    {
        self::$config[$key] = $value;
    }

    /**
     * Check if a configuration key exists.
     */
    public static function has(string $key): bool
    {
        return array_key_exists($key, self::$config);
    }

    /**
     * Get all configuration values.
     * 
     * @return array<string, mixed>
     */
    public static function all(): array
    {
        return self::$config;
    }

    // ─────────────────────────────────────────────────────────────
    // Testing Support
    // ─────────────────────────────────────────────────────────────

    /**
     * Reset all static state (for testing or long-running processes).
     */
    public static function reset(): void
    {
        self::$config = [];
        self::$loaded = false;
    }

    /**
     * Set multiple config values at once (for testing).
     * 
     * Usage in tests:
     *   Config::fake(['db.driver' => 'sqlite', 'app.debug' => true]);
     * 
     * @param array<string, mixed> $config
     */
    public static function fake(array $config): void
    {
        self::$config = $config;
        self::$loaded = true;
    }
}
