<?php

declare(strict_types=1);

namespace Core;

/**
 * Minimal service registry.
 * 
 * Enables testability without Laravel complexity.
 * Just: bind a name to a factory, resolve by name.
 * 
 * Usage:
 *   Registry::bind('cache', fn() => new FileCache());
 *   $cache = Registry::make('cache');
 * 
 * Testing:
 *   Registry::bind('cache', fn() => new ArrayCache());
 */
final class Registry
{
    /** @var array<string, callable> */
    private static array $bindings = [];
    
    /** @var array<string, object> */
    private static array $instances = [];
    
    /** @var array<string, bool> */
    private static array $singletons = [];

    /**
     * Bind a factory to a key.
     */
    public static function bind(string $id, callable $factory, bool $singleton = false): void
    {
        self::$bindings[$id] = $factory;
        self::$singletons[$id] = $singleton;
        
        // Clear cached instance if re-binding
        unset(self::$instances[$id]);
    }

    /**
     * Bind a factory as a singleton (resolved once, cached).
     */
    public static function singleton(string $id, callable $factory): void
    {
        self::bind($id, $factory, true);
    }

    /**
     * Bind an existing instance directly.
     */
    public static function instance(string $id, object $instance): void
    {
        self::$instances[$id] = $instance;
        self::$singletons[$id] = true;
    }

    /**
     * Resolve a binding.
     */
    public static function make(string $id): mixed
    {
        // Return cached singleton instance
        if (isset(self::$instances[$id])) {
            return self::$instances[$id];
        }

        // No binding registered
        if (!isset(self::$bindings[$id])) {
            throw new \RuntimeException("No binding found for '{$id}'");
        }

        // Resolve from factory
        $instance = (self::$bindings[$id])();

        // Cache if singleton
        if (self::$singletons[$id] ?? false) {
            self::$instances[$id] = $instance;
        }

        return $instance;
    }

    /**
     * Check if a binding exists.
     */
    public static function has(string $id): bool
    {
        return isset(self::$bindings[$id]) || isset(self::$instances[$id]);
    }

    /**
     * Remove a binding.
     */
    public static function forget(string $id): void
    {
        unset(
            self::$bindings[$id],
            self::$instances[$id],
            self::$singletons[$id]
        );
    }

    /**
     * Clear all bindings (for testing).
     */
    public static function flush(): void
    {
        self::$bindings = [];
        self::$instances = [];
        self::$singletons = [];
    }

    /**
     * Get all registered binding keys.
     */
    public static function keys(): array
    {
        return array_unique(array_merge(
            array_keys(self::$bindings),
            array_keys(self::$instances)
        ));
    }
}
