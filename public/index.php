<?php

declare(strict_types=1);

/**
 * Intent Framework - Entry Point
 * 
 * All requests are routed through this file.
 */

define('INTENT_START', microtime(true));
define('BASE_PATH', dirname(__DIR__));

// Autoloader
require BASE_PATH . '/vendor/autoload.php';

// Boot and run
$app = new Core\App();
$app->run();
