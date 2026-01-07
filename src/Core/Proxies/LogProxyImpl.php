<?php

declare(strict_types=1);

namespace Core\Proxies;

use Core\Contracts\LogProxy;
use Core\Log;

/**
 * Log proxy implementation.
 * 
 * Wraps static Log class for instance-style access via Registry.
 */
final class LogProxyImpl implements LogProxy
{
    /**
     * @param array<string, mixed> $context
     */
    public function debug(string $message, array $context = []): void
    {
        Log::debug($message, $context);
    }

    /**
     * @param array<string, mixed> $context
     */
    public function info(string $message, array $context = []): void
    {
        Log::info($message, $context);
    }

    /**
     * @param array<string, mixed> $context
     */
    public function warning(string $message, array $context = []): void
    {
        Log::warning($message, $context);
    }

    /**
     * @param array<string, mixed> $context
     */
    public function error(string $message, array $context = []): void
    {
        Log::error($message, $context);
    }

    /**
     * @param array<string, mixed> $context
     */
    public function critical(string $message, array $context = []): void
    {
        Log::critical($message, $context);
    }

    /**
     * @param array<string, mixed> $context
     */
    public function log(string $level, string $message, array $context = []): void
    {
        Log::log($level, $message, $context);
    }
}
