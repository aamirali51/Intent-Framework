<?php

declare(strict_types=1);

/**
 * PHPUnit Bootstrap
 * 
 * Sets up the testing environment.
 */

// Define base path for tests
define('BASE_PATH', dirname(__DIR__));

// Load autoloader
require BASE_PATH . '/vendor/autoload.php';

// Load test config (not production config)
\Core\Config::load(BASE_PATH . '/tests/config.php');
