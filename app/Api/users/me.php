<?php

declare(strict_types=1);

/**
 * Nested file-based route example.
 * 
 * URL: GET /api/users/me
 * File: public/api/users/me.php
 */

use Core\Request;
use Core\Response;

return function (Request $request, Response $response): Response {
    // In real app, get user from session/auth
    return $response->json([
        'id' => 1,
        'name' => 'Demo User',
        'email' => 'demo@example.com',
    ]);
};
