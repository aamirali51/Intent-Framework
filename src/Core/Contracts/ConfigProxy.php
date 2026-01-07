<?php

declare(strict_types=1);

namespace Core\Contracts;

/**
 * Config proxy interface for type-safe helper access.
 * 
 * Provides PHPStan Level 9 compatibility for config access via Registry.
 */
interface ConfigProxy
{
    /**
     * Get a configuration value.
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * Set a configuration value at runtime.
     */
    public function set(string $key, mixed $value): void;

    /**
     * Check if a configuration key exists.
     */
    public function has(string $key): bool;

    /**
     * Get all configuration values.
     * 
     * @return array<string, mixed>
     */
    public function all(): array;
}
