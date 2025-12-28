<?php

declare(strict_types=1);

namespace App\Handlers;

use Core\Request;
use Core\Response;

/**
 * Example handler for home routes.
 * 
 * Handlers are simple classes that group related route logic.
 * No base class needed. Just methods that take Request and return Response.
 */
final class HomeHandler
{
    public function index(Request $request, Response $response): Response
    {
        return $response->json([
            'message' => 'Welcome to Intent',
            'docs' => 'https://github.com/intent/framework',
        ]);
    }

    public function about(Request $request, Response $response): Response
    {
        return $response->json([
            'framework' => 'Intent',
            'version' => '0.1.0',
            'philosophy' => 'AI-native, zero-boilerplate',
        ]);
    }
}
