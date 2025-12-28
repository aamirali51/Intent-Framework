<?php

declare(strict_types=1);

namespace Core;

/**
 * Static route registration facade.
 * 
 * Provides static access to the router for cleaner syntax.
 * Supports middleware chaining via ->middleware() method.
 */
final class Route
{
    private static ?Router $router = null;

    /**
     * Set the router instance.
     */
    public static function setRouter(Router $router): void
    {
        self::$router = $router;
    }

    /**
     * Get the router instance.
     */
    public static function getRouter(): Router
    {
        if (self::$router === null) {
            throw new \RuntimeException('Router not initialized. Call Route::setRouter() first.');
        }
        return self::$router;
    }

    /**
     * Register a GET route.
     */
    public static function get(string $path, callable $handler): Router
    {
        return self::getRouter()->get($path, $handler);
    }

    /**
     * Register a POST route.
     */
    public static function post(string $path, callable $handler): Router
    {
        return self::getRouter()->post($path, $handler);
    }

    /**
     * Register a PUT route.
     */
    public static function put(string $path, callable $handler): Router
    {
        return self::getRouter()->put($path, $handler);
    }

    /**
     * Register a DELETE route.
     */
    public static function delete(string $path, callable $handler): Router
    {
        return self::getRouter()->delete($path, $handler);
    }

    /**
     * Register a route for any HTTP method.
     */
    public static function any(string $path, callable $handler): Router
    {
        return self::getRouter()->any($path, $handler);
    }

    /**
     * Register a route for a specific method.
     */
    public static function add(string $method, string $path, callable $handler): Router
    {
        return self::getRouter()->add($method, $path, $handler);
    }
}
