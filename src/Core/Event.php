<?php

declare(strict_types=1);

namespace Core;

/**
 * Simple event dispatcher.
 * 
 * No complex event objects - just name + data.
 * 
 * Usage:
 *   Event::listen('user.created', fn($user) => sendEmail($user));
 *   Event::dispatch('user.created', $user);
 */
final class Event
{
    /** @var array<string, array<callable>> */
    private static array $listeners = [];

    /**
     * Register a listener for an event.
     */
    public static function listen(string $event, callable $listener): void
    {
        if (!isset(self::$listeners[$event])) {
            self::$listeners[$event] = [];
        }

        self::$listeners[$event][] = $listener;
    }

    /**
     * Dispatch an event to all listeners.
     * 
     * @return array<int, mixed> Results from all listeners
     */
    public static function dispatch(string $event, mixed ...$data): array
    {
        $results = [];

        if (!isset(self::$listeners[$event])) {
            return $results;
        }

        foreach (self::$listeners[$event] as $listener) {
            $results[] = $listener(...$data);
        }

        return $results;
    }

    /**
     * Check if an event has listeners.
     */
    public static function hasListeners(string $event): bool
    {
        return isset(self::$listeners[$event]) && !empty(self::$listeners[$event]);
    }

    /**
     * Get all listeners for an event.
     * 
     * @return array<int, callable>
     */
    public static function getListeners(string $event): array
    {
        return self::$listeners[$event] ?? [];
    }

    /**
     * Remove all listeners for an event.
     */
    public static function forget(string $event): void
    {
        unset(self::$listeners[$event]);
    }

    /**
     * Remove all listeners.
     */
    public static function flush(): void
    {
        self::$listeners = [];
    }

    /**
     * Dispatch and halt on first non-null result.
     */
    public static function until(string $event, mixed ...$data): mixed
    {
        if (!isset(self::$listeners[$event])) {
            return null;
        }

        foreach (self::$listeners[$event] as $listener) {
            $result = $listener(...$data);
            if ($result !== null) {
                return $result;
            }
        }

        return null;
    }
}
