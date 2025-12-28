<?php

declare(strict_types=1);

namespace App\Handlers;

use Core\Request;
use Core\Response;

class UserHandler
{
    public function index(Request $request, Response $response): Response
    {
        return $response->json(['message' => 'Hello from UserHandler']);
    }
}