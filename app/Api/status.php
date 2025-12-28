<?php

declare(strict_types=1);

/**
 * File-based API route example.
 * 
 * URL: GET /api/status
 * File: public/api/status.php
 * 
 * This file MUST return a callable.
 */

use Core\Request;
use Core\Response;

return function (Request $request, Response $response, array $params): Response {
    return $response->json([
        'status' => 'ok',
        'method' => $request->method,
        'path' => $request->path,
        'timestamp' => time(),
    ]);
};
