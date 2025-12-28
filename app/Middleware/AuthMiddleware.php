<?php

declare(strict_types=1);

namespace App\Middleware;

use Core\Auth;
use Core\Middleware;
use Core\Request;
use Core\Response;

/**
 * Authentication Middleware
 * 
 * Requires user to be logged in.
 * Redirects to login page or returns 401 for API requests.
 */
class AuthMiddleware implements Middleware
{
    public function handle(Request $request, callable $next): Response
    {
        if (!Auth::check()) {
            // API request - return 401
            if ($request->wantsJson()) {
                return (new Response())->json([
                    'error' => 'Unauthorized',
                    'message' => 'Authentication required',
                ], 401);
            }

            // Web request - redirect to login
            return (new Response())->redirect('/login');
        }

        // User is authenticated, continue
        return $next($request);
    }
}
