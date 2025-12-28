<?php

/**
 * Root bootstrap for development.
 * 
 * This allows http://localhost/intent/ to work with Laragon.
 * In production, configure your web server to point directly to public/.
 */

// Forward everything to public/index.php
chdir(__DIR__ . '/public');
require __DIR__ . '/public/index.php';
