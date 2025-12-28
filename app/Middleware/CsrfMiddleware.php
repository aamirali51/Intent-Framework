<?php

declare(strict_types=1);

namespace App\Middleware;

use Core\Middleware;
use Core\Request;
use Core\Response;
use Core\Session;

/**
 * CSRF Protection Middleware
 * 
 * Protects against Cross-Site Request Forgery attacks.
 * Validates token on state-changing requests (POST, PUT, PATCH, DELETE).
 * 
 * Usage in forms:
 *   <input type="hidden" name="_token" value="<?= csrf_token() ?>">
 *   
 * Or use the helper:
 *   <?= csrf_field() ?>
 */
class CsrfMiddleware implements Middleware
{
    /**
     * HTTP methods that require CSRF validation.
     */
    private const PROTECTED_METHODS = ['POST', 'PUT', 'PATCH', 'DELETE'];

    /**
     * Session key for storing the CSRF token.
     */
    private const TOKEN_KEY = '_csrf_token';

    /**
     * Handle the request.
     */
    public function handle(Request $request, callable $next): Response
    {
        // Ensure session is started
        Session::start();

        // Generate token if not exists
        if (!Session::has(self::TOKEN_KEY)) {
            $this->regenerateToken();
        }

        // Skip validation for safe methods (GET, HEAD, OPTIONS)
        if (!in_array($request->method, self::PROTECTED_METHODS, true)) {
            return $next($request);
        }

        // Get token from request
        $token = $this->getTokenFromRequest($request);

        // Validate token
        if (!$this->isValidToken($token)) {
            // API request - return 403 JSON
            if ($request->wantsJson()) {
                return (new Response())->json([
                    'error' => 'Forbidden',
                    'message' => 'CSRF token mismatch',
                ], 403);
            }

            // Web request - redirect back with error
            Session::flash('error', 'Session expired. Please try again.');
            
            $referer = $request->header('referer', '/');
            return (new Response())->redirect($referer, 403);
        }

        // Token valid, continue
        return $next($request);
    }

    /**
     * Get CSRF token from request.
     * 
     * Checks: POST _token, X-CSRF-TOKEN header, X-XSRF-TOKEN header
     */
    private function getTokenFromRequest(Request $request): ?string
    {
        // Check POST body
        $token = $request->post('_token');
        if ($token !== null) {
            return $token;
        }

        // Check JSON body
        $json = $request->json();
        if ($json !== null && isset($json['_token'])) {
            return $json['_token'];
        }

        // Check headers (for AJAX)
        $token = $request->header('x-csrf-token');
        if ($token !== null) {
            return $token;
        }

        $token = $request->header('x-xsrf-token');
        if ($token !== null) {
            return $token;
        }

        return null;
    }

    /**
     * Validate the given token against session token.
     */
    private function isValidToken(?string $token): bool
    {
        if ($token === null) {
            return false;
        }

        $sessionToken = Session::get(self::TOKEN_KEY);
        if ($sessionToken === null) {
            return false;
        }

        // Timing-safe comparison
        return hash_equals($sessionToken, $token);
    }

    /**
     * Generate a new CSRF token.
     */
    public static function regenerateToken(): string
    {
        $token = bin2hex(random_bytes(32));
        Session::set(self::TOKEN_KEY, $token);
        return $token;
    }

    /**
     * Get the current CSRF token.
     */
    public static function getToken(): string
    {
        Session::start();
        
        if (!Session::has(self::TOKEN_KEY)) {
            return self::regenerateToken();
        }

        return Session::get(self::TOKEN_KEY);
    }
}
