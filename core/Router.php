<?php

declare(strict_types=1);

namespace Core;

/**
 * Simple, explicit router.
 * 
 * Supports per-route middleware (no global stack).
 * Just method + path = handler + optional middleware.
 */
final class Router
{
    /** @var array<string, array<string, array{handler: callable, middleware: array}>> */
    private array $routes = [];

    /** @var array<string, array<string, array{handler: callable, middleware: array}>> */
    private array $dynamicRoutes = [];

    /** @var array Middleware for the last registered route */
    private array $lastRoute = ['method' => '', 'path' => '', 'isDynamic' => false];

    /**
     * Register a GET route.
     */
    public function get(string $path, callable $handler): self
    {
        return $this->add('GET', $path, $handler);
    }

    /**
     * Register a POST route.
     */
    public function post(string $path, callable $handler): self
    {
        return $this->add('POST', $path, $handler);
    }

    /**
     * Register a PUT route.
     */
    public function put(string $path, callable $handler): self
    {
        return $this->add('PUT', $path, $handler);
    }

    /**
     * Register a DELETE route.
     */
    public function delete(string $path, callable $handler): self
    {
        return $this->add('DELETE', $path, $handler);
    }

    /**
     * Register a route for any method.
     */
    public function any(string $path, callable $handler): self
    {
        foreach (['GET', 'POST', 'PUT', 'DELETE', 'PATCH'] as $method) {
            $this->add($method, $path, $handler);
        }
        return $this;
    }

    /**
     * Add a route.
     */
    public function add(string $method, string $path, callable $handler): self
    {
        $path = '/' . trim($path, '/');
        $isDynamic = str_contains($path, '{');

        $routeData = [
            'handler' => $handler,
            'middleware' => [],
        ];

        if ($isDynamic) {
            $this->dynamicRoutes[$method][$path] = $routeData;
        } else {
            $this->routes[$method][$path] = $routeData;
        }

        // Track last route for middleware chaining
        $this->lastRoute = ['method' => $method, 'path' => $path, 'isDynamic' => $isDynamic];

        return $this;
    }

    /**
     * Add middleware to the last registered route.
     * 
     * @param array<string|callable>|string|callable $middleware
     */
    public function middleware(array|string|callable $middleware): self
    {
        $middleware = is_array($middleware) ? $middleware : [$middleware];
        
        $method = $this->lastRoute['method'];
        $path = $this->lastRoute['path'];
        $isDynamic = $this->lastRoute['isDynamic'];

        if ($isDynamic) {
            $this->dynamicRoutes[$method][$path]['middleware'] = array_merge(
                $this->dynamicRoutes[$method][$path]['middleware'],
                $middleware
            );
        } else {
            $this->routes[$method][$path]['middleware'] = array_merge(
                $this->routes[$method][$path]['middleware'],
                $middleware
            );
        }

        return $this;
    }

    /**
     * Check if a route is registered.
     */
    public function hasRoute(string $method, string $path): bool
    {
        $path = '/' . trim($path, '/');
        return isset($this->routes[$method][$path]) 
            || isset($this->dynamicRoutes[$method][$path]);
    }

    /**
     * Dispatch the request to matching handler.
     * 
     * Resolution order (configurable via routing.file_routes_first):
     * - Default: explicit → file-based (predictable)
     * - Prototyping: file-based → explicit (quick iteration)
     * 
     * @return array{handler: callable, params: array, middleware: array}|null
     */
    public function dispatch(Request $request): ?array
    {
        $method = $request->method;
        $path = $request->path;

        // Check config for resolution order
        $fileRoutesFirst = Config::get('routing.file_routes_first', false);

        if ($fileRoutesFirst) {
            // Prototyping mode: file routes first
            return $this->dispatchFileFirst($method, $path)
                ?? $this->dispatchExplicit($method, $path);
        }

        // Default: explicit routes first
        return $this->dispatchExplicit($method, $path)
            ?? $this->dispatchFileRoute($path);
    }

    /**
     * Dispatch using explicit routes only.
     * 
     * @return array{handler: callable, params: array, middleware: array}|null
     */
    private function dispatchExplicit(string $method, string $path): ?array
    {
        // Static routes
        if (isset($this->routes[$method][$path])) {
            $route = $this->routes[$method][$path];
            return [
                'handler' => $route['handler'],
                'params' => [],
                'middleware' => $route['middleware'],
            ];
        }

        // Dynamic routes
        if (isset($this->dynamicRoutes[$method])) {
            foreach ($this->dynamicRoutes[$method] as $pattern => $route) {
                $params = $this->matchDynamic($pattern, $path);
                if ($params !== null) {
                    return [
                        'handler' => $route['handler'],
                        'params' => $params,
                        'middleware' => $route['middleware'],
                    ];
                }
            }
        }

        return null;
    }

    /**
     * Dispatch using file-based route only.
     */
    private function dispatchFileRoute(string $path): ?array
    {
        $handler = $this->resolveFileRoute($path);
        if ($handler === null) {
            return null;
        }
        return [
            'handler' => $handler,
            'params' => [],
            'middleware' => [],  // File routes don't have middleware
        ];
    }

    /**
     * Dispatch with file routes first (prototyping mode).
     */
    private function dispatchFileFirst(string $method, string $path): ?array
    {
        // Try file route first
        $result = $this->dispatchFileRoute($path);
        if ($result !== null) {
            return $result;
        }

        // Fall back to explicit routes
        return $this->dispatchExplicit($method, $path);
    }

    /**
     * Resolve a file-based route.
     * 
     * SECURITY: Routes are in app/Api/ (outside document root)
     * Maps: /api/users/list → app/Api/users/list.php
     * 
     * The PHP file must return a callable.
     */
    private function resolveFileRoute(string $path): ?callable
    {
        // Check if file-based routing is enabled
        if (!Config::get('feature.file_routes', true)) {
            return null;
        }

        // Only handle /api/* paths
        if (!str_starts_with($path, '/api/')) {
            return null;
        }

        // Strip /api prefix and build file path
        // /api/users/list → app/Api/users/list.php
        $relativePath = substr($path, 4); // Remove '/api'
        $filePath = BASE_PATH . '/app/Api' . $relativePath . '.php';

        // Normalize path separators
        $filePath = str_replace('/', DIRECTORY_SEPARATOR, $filePath);

        // Security: prevent directory traversal
        $realPath = realpath($filePath);
        $apiDir = realpath(BASE_PATH . '/app/Api');

        if ($realPath === false || $apiDir === false) {
            return null;
        }

        if (!str_starts_with($realPath, $apiDir)) {
            return null;
        }

        if (!is_file($realPath)) {
            return null;
        }

        // Require file and expect it to return a callable
        $handler = require $realPath;

        if (!is_callable($handler)) {
            throw new \RuntimeException("File route must return a callable: {$path}");
        }

        return $handler;
    }

    /**
     * Match a dynamic route pattern against a path.
     * 
     * @return array<string, string>|null
     */
    private function matchDynamic(string $pattern, string $path): ?array
    {
        $patternParts = explode('/', trim($pattern, '/'));
        $pathParts = explode('/', trim($path, '/'));

        if (count($patternParts) !== count($pathParts)) {
            return null;
        }

        $params = [];

        foreach ($patternParts as $i => $part) {
            if (str_starts_with($part, '{') && str_ends_with($part, '}')) {
                $name = substr($part, 1, -1);
                $params[$name] = $pathParts[$i];
            } elseif ($part !== $pathParts[$i]) {
                return null;
            }
        }

        return $params;
    }
}
