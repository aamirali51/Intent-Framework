<?php

declare(strict_types=1);

namespace Core\Contracts;

/**
 * Session proxy interface for type-safe helper access.
 * 
 * Provides PHPStan Level 9 compatibility for session() helper.
 */
interface SessionProxy
{
    /**
     * Get a session value.
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * Set a session value.
     */
    public function set(string $key, mixed $value): void;

    /**
     * Check if a session key exists.
     */
    public function has(string $key): bool;

    /**
     * Remove a session value.
     */
    public function forget(string $key): void;

    /**
     * Get all session data.
     * 
     * @return array<string, mixed>
     */
    public function all(): array;

    /**
     * Clear all session data.
     */
    public function clear(): void;

    /**
     * Destroy the session completely.
     */
    public function destroy(): void;

    /**
     * Regenerate session ID.
     */
    public function regenerate(): void;

    /**
     * Set a flash message.
     */
    public function flash(string $key, mixed $value): void;

    /**
     * Get a flash message.
     */
    public function getFlash(string $key, mixed $default = null): mixed;
}
