<?php

declare(strict_types=1);

namespace Core;

/**
 * Security headers middleware.
 * 
 * Adds essential security headers to all responses.
 * 
 * Usage:
 *   $router->group(['middleware' => SecurityHeaders::class], function($router) {
 *       // All routes here get security headers
 *   });
 * 
 * Or per-route:
 *   $router->get('/admin', $handler)->middleware(new SecurityHeaders());
 */
final class SecurityHeaders
{
    /** @var array<string, string> */
    private array $options;

    /**
     * Default security headers.
     */
    private const DEFAULTS = [
        'X-Content-Type-Options' => 'nosniff',
        'X-Frame-Options' => 'SAMEORIGIN',
        'X-XSS-Protection' => '1; mode=block',
        'Referrer-Policy' => 'strict-origin-when-cross-origin',
        'Permissions-Policy' => 'geolocation=(), microphone=(), camera=()',
    ];

    /**
     * @param array<string, string> $options
     */
    public function __construct(array $options = [])
    {
        $this->options = array_merge(self::DEFAULTS, $options);
    }

    /**
     * Handle the request as middleware.
     */
    public function __invoke(Request $request, Response $response, callable $next): Response
    {
        // Add security headers
        foreach ($this->options as $header => $value) {
            if ($value !== null && $value !== '') {
                $response->header($header, $value);
            }
        }

        return $next($request, $response);
    }

    /**
     * Create middleware with Content Security Policy.
     * 
     * Usage:
     *   ->middleware(SecurityHeaders::withCSP("default-src 'self'"))
     */
    public static function withCSP(string $policy): self
    {
        return new self([
            'Content-Security-Policy' => $policy,
        ]);
    }

    /**
     * Create middleware that allows iframes from same origin.
     */
    public static function allowFrames(): self
    {
        return new self([
            'X-Frame-Options' => 'SAMEORIGIN',
        ]);
    }

    /**
     * Create middleware that denies all iframes.
     */
    public static function denyFrames(): self
    {
        return new self([
            'X-Frame-Options' => 'DENY',
        ]);
    }

    /**
     * Create strict middleware for sensitive pages.
     */
    public static function strict(): self
    {
        return new self([
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'DENY',
            'X-XSS-Protection' => '1; mode=block',
            'Referrer-Policy' => 'no-referrer',
            'Content-Security-Policy' => "default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'",
            'Permissions-Policy' => 'geolocation=(), microphone=(), camera=(), payment=()',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
            'Pragma' => 'no-cache',
        ]);
    }
}
