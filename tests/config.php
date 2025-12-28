<?php

declare(strict_types=1);

/**
 * Test Configuration
 * 
 * Used by PHPUnit tests instead of production config.
 */

return [
    'app.name' => 'Intent Test',
    'app.env' => 'testing',
    'app.debug' => true,
    'app.url' => 'http://localhost',

    'routing.file_routes_first' => false,

    'feature.schema' => false,  // Disable for most tests
    'feature.file_routes' => true,

    // Test database (SQLite in memory for speed)
    'db.driver' => 'sqlite',
    'db.host' => ':memory:',
    'db.name' => ':memory:',
    'db.user' => '',
    'db.pass' => '',

    'path.views' => BASE_PATH . '/views',
    'path.cache' => BASE_PATH . '/storage/cache',
];
