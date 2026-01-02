<?php

declare(strict_types=1);

namespace App\Middleware;

use Core\ApiToken;
use Core\Auth;
use Core\Middleware;
use Core\Request;
use Core\Response;

/**
 * Authentication Middleware
 * 
 * Supports both session-based (web) and Bearer token (API) authentication.
 * 
 * For web requests:
 *   - Checks session for logged-in user
 *   - Redirects to /login if not authenticated
 * 
 * For API requests:
 *   - Extracts Bearer token from Authorization header
 *   - Validates token and sets user for request
 *   - Returns 401 JSON if invalid/missing
 */
class AuthMiddleware implements Middleware
{
    public function handle(Request $request, callable $next): Response
    {
        // Check session first (web auth)
        if (Auth::check()) {
            return $next($request);
        }

        // Check Bearer token (API auth)
        $token = ApiToken::fromRequest($request);
        if ($token !== null) {
            $user = ApiToken::validate($token);
            if ($user !== null) {
                Auth::setUser($user);
                return $next($request);
            }
        }

        // Not authenticated
        if ($request->wantsJson()) {
            return (new Response())->json([
                'error' => 'Unauthorized',
                'message' => 'Authentication required',
            ], 401);
        }

        // Web request - redirect to login
        return (new Response())->redirect('/login');
    }
}
