<?php

declare(strict_types=1);

namespace App\Middleware;

use Core\Middleware;
use Core\Request;
use Core\Response;

/**
 * Example: Logging Middleware
 * 
 * Logs request info before and response info after.
 */
class LogMiddleware implements Middleware
{
    public function handle(Request $request, callable $next): Response
    {
        $start = microtime(true);

        // Log incoming request
        error_log("[Request] {$request->method} {$request->path}");

        // Continue to next middleware/handler
        $response = $next($request);

        // Log response
        $duration = round((microtime(true) - $start) * 1000, 2);
        error_log("[Response] {$response->getStatus()} ({$duration}ms)");

        return $response;
    }
}
