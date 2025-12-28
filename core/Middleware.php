<?php

declare(strict_types=1);

namespace Core;

/**
 * Middleware interface.
 * 
 * Middleware receives the request and a $next callable.
 * Call $next($request) to continue the pipeline.
 * Return a Response to short-circuit.
 */
interface Middleware
{
    /**
     * Handle the request.
     * 
     * @param Request $request The incoming request
     * @param callable $next The next middleware in the pipeline
     * @return Response The response
     */
    public function handle(Request $request, callable $next): Response;
}
