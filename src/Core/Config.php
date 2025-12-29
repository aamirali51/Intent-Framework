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
     */
    public static function all(): array
    {
        return self::$config;
    }
}
