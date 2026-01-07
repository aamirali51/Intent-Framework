<?php

declare(strict_types=1);

namespace Core\Proxies;

use Core\Auth;
use Core\Contracts\AuthProxy;

/**
 * Auth proxy implementation.
 * 
 * Wraps static Auth class for instance-style access via Registry.
 */
final class AuthProxyImpl implements AuthProxy
{
    public function check(): bool
    {
        return Auth::check();
    }

    public function guest(): bool
    {
        return Auth::guest();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function user(): ?array
    {
        return Auth::user();
    }

    public function id(): ?int
    {
        return Auth::id();
    }

    public function logout(): void
    {
        Auth::logout();
    }

    /**
     * @param array{email?: string, password: string} $credentials
     */
    public function attempt(array $credentials): bool
    {
        return Auth::attempt($credentials);
    }

    /**
     * @param array<string, mixed> $user
     */
    public function login(array $user): void
    {
        Auth::login($user);
    }
}
