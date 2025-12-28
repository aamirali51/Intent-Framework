<?php

declare(strict_types=1);

namespace App\Middleware;

use Core\Auth;
use Core\Middleware;
use Core\Request;
use Core\Response;

/**
 * Guest Middleware
 * 
 * Only allows guests (non-authenticated users).
 * Redirects authenticated users away from login/register pages.
 */
class GuestMiddleware implements Middleware
{
    public function handle(Request $request, callable $next): Response
    {
        if (Auth::check()) {
            // Already logged in - redirect to dashboard
            if ($request->wantsJson()) {
                return (new Response())->json([
                    'error' => 'Already authenticated',
                ], 400);
            }

            return (new Response())->redirect('/dashboard');
        }

        // User is guest, continue
        return $next($request);
    }
}
