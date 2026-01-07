<?php

declare(strict_types=1);

namespace Core\Contracts;

/**
 * Auth proxy interface for type-safe helper access.
 * 
 * Provides PHPStan Level 9 compatibility for auth() helper.
 */
interface AuthProxy
{
    /**
     * Check if a user is logged in.
     */
    public function check(): bool;

    /**
     * Check if user is a guest (not logged in).
     */
    public function guest(): bool;

    /**
     * Get the current authenticated user.
     * 
     * @return array<string, mixed>|null
     */
    public function user(): ?array;

    /**
     * Get the current user's ID.
     */
    public function id(): ?int;

    /**
     * Log out the current user.
     */
    public function logout(): void;

    /**
     * Attempt to log in with credentials.
     * 
     * @param array{email?: string, password: string} $credentials
     */
    public function attempt(array $credentials): bool;

    /**
     * Log in a user directly.
     * 
     * @param array<string, mixed> $user
     */
    public function login(array $user): void;
}
