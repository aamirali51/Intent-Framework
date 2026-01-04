<?php

declare(strict_types=1);

namespace Core;

/**
 * HTTP Request wrapper.
 * 
 * Immutable representation of the current request.
 */
final class Request
{
    public readonly string $method;
    public readonly string $uri;
    public readonly string $path;
    /** @var array<string, mixed> */
    public readonly array $query;
    /** @var array<string, mixed> */
    public readonly array $post;
    /** @var array<string, mixed> */
    public readonly array $server;
    /** @var array<string, string> */
    public readonly array $headers;
    public readonly ?string $body;

    public function __construct()
    {
        /** @var string $requestMethod */
        $requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $this->method = strtoupper($requestMethod);
        /** @var string $requestUri */
        $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
        $this->uri = $requestUri;
        $this->path = $this->parsePath($this->uri);
        $this->query = $_GET;
        $this->post = $_POST;
        $this->server = $_SERVER;
        $this->headers = $this->parseHeaders();
        $this->body = file_get_contents('php://input') ?: null;
    }

    /**
     * Get a query parameter.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->query[$key] ?? $default;
    }

    /**
     * Get a POST parameter.
     */
    public function post(string $key, mixed $default = null): mixed
    {
        return $this->post[$key] ?? $default;
    }

    /**
     * Get a header value.
     */
    public function header(string $key, mixed $default = null): mixed
    {
        $key = strtolower($key);
        return $this->headers[$key] ?? $default;
    }

    /**
     * Get JSON decoded body.
     * 
     * @return array<string, mixed>|null
     */
    public function json(): ?array
    {
        if ($this->body === null) {
            return null;
        }

        $decoded = json_decode($this->body, true);
        return is_array($decoded) ? $decoded : null;
    }

    /**
     * Check if request is AJAX.
     */
    public function isAjax(): bool
    {
        return $this->header('x-requested-with') === 'XMLHttpRequest';
    }

    /**
     * Check if request expects JSON.
     */
    public function wantsJson(): bool
    {
        /** @var string $accept */
        $accept = $this->header('accept', '') ?? '';
        return str_contains($accept, 'application/json');
    }

    /**
     * Get the client IP address.
     * 
     * Checks common proxy headers first, falls back to REMOTE_ADDR.
     */
    public function ip(): string
    {
        // Check for forwarded IP (behind proxy/load balancer)
        /** @var string|null $forwardedFor */
        $forwardedFor = $this->server['HTTP_X_FORWARDED_FOR'] ?? null;
        if ($forwardedFor !== null) {
            // Take the first IP in the chain (original client)
            $ips = array_map('trim', explode(',', $forwardedFor));
            return $ips[0];
        }

        // Check for real IP header (Cloudflare, etc.)
        if (isset($this->server['HTTP_X_REAL_IP'])) {
            return $this->server['HTTP_X_REAL_IP'];
        }

        // Fallback to direct connection IP
        return $this->server['REMOTE_ADDR'] ?? '127.0.0.1';
    }

    private function parsePath(string $uri): string
    {
        $path = parse_url($uri, PHP_URL_PATH);
        if ($path === false || $path === null) {
            $path = '/';
        }
        return '/' . trim($path, '/');
    }

    /**
     * @return array<string, string>
     */
    private function parseHeaders(): array
    {
        $headers = [];
        foreach ($this->server as $key => $value) {
            if (is_string($key) && str_starts_with($key, 'HTTP_') && is_string($value)) {
                $name = strtolower(str_replace('_', '-', substr($key, 5)));
                $headers[$name] = $value;
            }
        }
        return $headers;
    }
}
