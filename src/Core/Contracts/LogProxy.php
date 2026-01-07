<?php

declare(strict_types=1);

namespace Core\Contracts;

/**
 * Log proxy interface for type-safe helper access.
 * 
 * Provides PHPStan Level 9 compatibility for logger() helper.
 */
interface LogProxy
{
    /**
     * Log a debug message.
     * 
     * @param array<string, mixed> $context
     */
    public function debug(string $message, array $context = []): void;

    /**
     * Log an info message.
     * 
     * @param array<string, mixed> $context
     */
    public function info(string $message, array $context = []): void;

    /**
     * Log a warning message.
     * 
     * @param array<string, mixed> $context
     */
    public function warning(string $message, array $context = []): void;

    /**
     * Log an error message.
     * 
     * @param array<string, mixed> $context
     */
    public function error(string $message, array $context = []): void;

    /**
     * Log a critical message.
     * 
     * @param array<string, mixed> $context
     */
    public function critical(string $message, array $context = []): void;

    /**
     * Log a message at any level.
     * 
     * @param array<string, mixed> $context
     */
    public function log(string $level, string $message, array $context = []): void;
}
