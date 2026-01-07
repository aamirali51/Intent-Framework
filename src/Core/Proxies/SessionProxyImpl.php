<?php

declare(strict_types=1);

namespace Core\Proxies;

use Core\Contracts\SessionProxy;
use Core\Session;

/**
 * Session proxy implementation.
 * 
 * Wraps static Session class for instance-style access via Registry.
 */
final class SessionProxyImpl implements SessionProxy
{
    public function get(string $key, mixed $default = null): mixed
    {
        return Session::get($key, $default);
    }

    public function set(string $key, mixed $value): void
    {
        Session::set($key, $value);
    }

    public function has(string $key): bool
    {
        return Session::has($key);
    }

    public function forget(string $key): void
    {
        Session::forget($key);
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return Session::all();
    }

    public function clear(): void
    {
        Session::clear();
    }

    public function destroy(): void
    {
        Session::destroy();
    }

    public function regenerate(): void
    {
        Session::regenerate();
    }

    public function flash(string $key, mixed $value): void
    {
        Session::flash($key, $value);
    }

    public function getFlash(string $key, mixed $default = null): mixed
    {
        return Session::getFlash($key, $default);
    }
}
