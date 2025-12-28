<?php

declare(strict_types=1);

/**
 * Intent Framework - Configuration
 * 
 * Flat array, no magic. Access via Config::get('key').
 */

return [
    // Application
    'app.name' => 'Intent',
    'app.env' => 'development',
    'app.debug' => true,
    'app.url' => 'http://localhost',

    // Routing
    // When true: file routes → explicit routes (quick prototyping)
    // When false: explicit routes → file routes (default, predictable)
    'routing.file_routes_first' => false,

    // Features (dev convenience toggles)
    'feature.schema' => true,       // Auto-schema inference (dev only)
    'feature.file_routes' => true,  // File-based routing in app/Api/ (disable in prod)

    // Database
    'db.driver' => 'mysql',
    'db.host' => 'localhost',
    'db.port' => 3306,
    'db.name' => 'intent',
    'db.user' => 'root',
    'db.pass' => '',

    // Paths
    'path.views' => BASE_PATH . '/app/views',
    'path.cache' => BASE_PATH . '/storage/cache',
];
