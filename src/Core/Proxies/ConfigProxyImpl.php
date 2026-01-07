<?php

declare(strict_types=1);

namespace Core\Proxies;

use Core\Config;
use Core\Contracts\ConfigProxy;

/**
 * Config proxy implementation.
 * 
 * Wraps static Config class for instance-style access via Registry.
 */
final class ConfigProxyImpl implements ConfigProxy
{
    public function get(string $key, mixed $default = null): mixed
    {
        return Config::get($key, $default);
    }

    public function set(string $key, mixed $value): void
    {
        Config::set($key, $value);
    }

    public function has(string $key): bool
    {
        return Config::has($key);
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return Config::all();
    }
}
