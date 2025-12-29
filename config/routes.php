<?php

declare(strict_types=1);

use Core\Route;
use Core\Request;
use Core\Response;
use Core\Config;

/**
 * Application Routes
 * 
 * Manual routes are checked BEFORE file routes.
 * File routes in public/api/*.php are the fallback.
 */

// ─────────────────────────────────────────────────────────────
// Static Routes (override file routes)
// ─────────────────────────────────────────────────────────────

// Uncomment below to replace the default welcome page:
// Route::get('/', fn(Request $req, Response $res) => 
//     $res->json([
//         'framework' => Config::get('app.name'),
//         'message' => 'Welcome to Intent',
//     ])
// );

Route::get('/health', fn(Request $req, Response $res) => 
    $res->json([
        'status' => 'ok',
        'uptime' => round((microtime(true) - INTENT_START) * 1000, 2) . 'ms',
    ])
);

// ─────────────────────────────────────────────────────────────
// Dynamic Routes
// ─────────────────────────────────────────────────────────────

Route::get('/hello/{name}', fn(Request $req, Response $res, array $params) => 
    $res->json(['message' => "Hello, {$params['name']}!"])
);

Route::get('/users/{id}', fn(Request $req, Response $res, array $params) => 
    $res->json(['user_id' => (int) $params['id']])
);

// ─────────────────────────────────────────────────────────────
// POST Routes
// ─────────────────────────────────────────────────────────────

Route::post('/echo', fn(Request $req, Response $res) => 
    $res->json(['received' => $req->json() ?? $req->post])
);

// ─────────────────────────────────────────────────────────────
// File-based routes (automatic, no registration needed)
// ─────────────────────────────────────────────────────────────
// /api/status     → public/api/status.php
// /api/users/me   → public/api/users/me.php
